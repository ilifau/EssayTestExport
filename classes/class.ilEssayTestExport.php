<?php
// Copyright (c) 2019 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE
/**
 * Export the text content of answered questions
 */
class ilEssayTestExport
{
    /** @var ilEssayTestExportPlugin $plugin */
    protected $plugin;

    /** @var ilObjTest $testObj */
    protected $testObj;

    /** @var array  */
    protected $supported_types = ['assTextQuestion'];

    /** @var  ilDB $db */
    protected $db;

    /** @var ilLanguage $lng */
    protected $lng;

    /** @var \ILIAS\Filesystem\Filesystem  */
    protected $tempfs;

    /** @var string */
    protected $workdir;

    /* @var array active_is => ['name' => string, 'fullname' => string, 'login' => string ]*/
    protected $paricipants;

    /**
     * Constructor
     * @param ilEssayTestExportPlugin $plugin
     * @param ilObjTest $test
     */
    public function __construct($plugin, $test)
    {
        global $DIC;

        $this->db = $DIC->database();
        $this->lng = $DIC->language();

        $this->plugin = $plugin;
        $this->testObj = $test;
        $this->participants = $this->testObj->getParticipants();
    }


    /**
     * Create the export file
     * @param string $zipfile
     * @throws Exception $e
     */
    public function createExportFile($zipfile)
    {
        global $DIC;

        $this->workdir = md5(uniqid());
        $this->tempfs = $DIC->filesystem()->temp();
        $this->tempfs->createDir($this->workdir);

        $types = $this->getQuestionTypes();
        foreach ($this->getQuestions($types) as $question_id => $question_data)
        {
            $quest_part = $this->sanitizeFilename($question_id. ' ' .$question_data['title'], ' ');

            foreach ($this->getSolutions($question_id) as $active_id => $passes)
            {
                if (!isset($this->participants[$active_id]))
                {
                    continue;
                }

                $part_part = $this->sanitizeFilename($this->participants[$active_id]['name'] . ', ' . $this->participants[$active_id]['login'], ' ');

                foreach ($passes as $pass => $values)
                {
                    $pass_part = 'pass' . ($pass + 1);

                    switch ($question_data['type'])
                    {
                        case 'assTextQuestion':
                            $content = $this->assTextQuestionContent($values);
                            break;

                         default:
                            continue;
                    }

                    $path =  $this->workdir. '/'. $quest_part . '/' . $part_part . ', ' . $pass_part . '.txt';
                    $this->tempfs->write($path, $content);
                }
            }
        }


        ilUtil::zip(CLIENT_DATA_DIR . "/temp/". $this->workdir, $zipfile, true);
        $this->tempfs->deleteDir($this->workdir);
    }

    /**
     * Get the relevant question types
     * @return array type_id => type_tag
     */
    protected function getQuestionTypes()
    {
        $types = [];

        $query = "SELECT question_type_id, type_tag FROM qpl_qst_type WHERE "
            . $this->db->in('type_tag', $this->supported_types, false, 'text');

        $result = $this->db->query($query);
        while ($row = $this->db->fetchAssoc($result))
        {
            $types[$row['question_type_id']] = $row['type_tag'];
        }
        return $types;
    }

    /**
     * Get the answered questions
     * @param array $types type_id => type_tag
     * @return array question_id => ['title' => string, 'type' => string ]
     */
    protected function getQuestions($types)
    {
        $questions = [];

        $query = "
            SELECT DISTINCT t.question_fi, q.question_type_fi, q.title
            FROM tst_test_result t
            INNER JOIN qpl_questions q ON t.question_fi = q.question_id
            WHERE " . $this->db->in('active_fi', array_keys($this->participants), false, 'integer')
            . " AND ". $this->db->in('question_type_fi', array_keys($types), false, 'integer');

        $result = $this->db->query($query);
        while ($row = $this->db->fetchAssoc($result))
        {
            $questions[$row['question_fi']] = ['title' => $row['title'], 'type'=> $types[$row['question_type_fi']]];
        }

        return $questions;
    }

    /**
     * Getthe stored user solutions of a question
     * @param int $question_id
     * @return array active_id => pass => [[value1 => string, 'value2' => string ], ... ]
     */
    protected function getSolutions($question_id)
    {
        $solutions = [];

        $query = "
            SELECT active_fi, pass, value1, value2 
            FROM tst_solutions
            WHERE authorized = 1
            AND question_fi = " . $this->db->quote($question_id, 'text')
            ." ORDER BY solution_id" ;

        $result = $this->db->query($query);
        while ($row = $this->db->fetchAssoc($result))
        {
            $solutions[$row['active_fi']][$row['pass']][] = ['value1' => $row['value1'], 'value2' => $row['value2']];
        }

        return $solutions;
    }

    /**
     * Extract the text content of a stored user solution
     * @param array $values (array of assoc solution records with 'value1' and 'value2'
     * @return string
     */
    protected function assTextQuestionContent($values)
    {
        $record = end($values);
        return ilUtil::stripSlashes($record['value1'], true, 'none');
    }


    /**
     * Sanitize a file name
     * @see http://www.house6.com/blog/?p=83
     * @param string $f
     * @param string $a_space_replace
     * @return mixed|string
     */
    public function sanitizeFilename($f, $a_space_replace = '_') {
        // a combination of various methods
        // we don't want to convert html entities, or do any url encoding
        // we want to retain the "essence" of the original file name, if possible
        // char replace table found at:
        // http://www.php.net/manual/en/function.strtr.php#98669
        $replace_chars = array(
            'Š'=>'S', 'š'=>'s', 'Ð'=>'Dj','Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'Ae',
            'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I',
            'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'Oe', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U',
            'Û'=>'U', 'Ü'=>'Ue', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'ss','à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'ae', 'ä'=>'a',
            'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i',
            'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'oe', 'ø'=>'o', 'ù'=>'u',
            'ü'=>'ue', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', 'ƒ'=>'f'
        );
        $f = strtr($f, $replace_chars);
        // convert & to "and", @ to "at", and # to "number"
        $f = preg_replace(array('/[\&]/', '/[\@]/', '/[\#]/'), array('-and-', '-at-', '-number-'), $f);
        $f = preg_replace('/[^(\x20-\x7F)]*/','', $f); // removes any special chars we missed
        $f = str_replace(' ', $a_space_replace, $f); // convert space to hyphen
        $f = str_replace("'", '', $f); 	// removes single apostrophes
        $f = str_replace('"', '', $f);  // removes double apostrophes
        $f = preg_replace('/[^\w\-\.\,_ ]+/', '', $f); // remove non-word chars (leaving hyphens and periods)
        $f = preg_replace('/[\-]+/', '-', $f); // converts groups of hyphens into one
        $f = preg_replace('/[_]+/', '_', $f); // converts groups of dashes into one
        return $f;
    }

}