<?php

namespace backend\controllers;

use Yii;
use backend\models\Picking;
use backend\models\PickingSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use backend\models\Modelfile;
use yii\web\UploadedFile;
use yii\web\ForbiddenHttpException;
use yii\filters\AccessControl;

/**
 * PickingController implements the CRUD actions for Picking model.
 */
class DbrestoreController extends Controller
{
    public $enableCsrfValidation = false;

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access'=>[
                'class'=>AccessControl::className(),
                'denyCallback' => function ($rule, $action) {
                    throw new ForbiddenHttpException('คุณไม่ได้รับอนุญาติให้เข้าใช้งาน!');
                },
                'rules'=>[
//                    [
//                        'allow'=>true,
//                        'actions'=>['index','create','update','delete','view'],
//                        'roles'=>['@'],
//                    ]
                    [
                        'allow'=>true,
                        'roles'=>['@'],
                        'matchCallback'=>function($rule,$action){
                            $currentRoute = Yii::$app->controller->getRoute();
                            if(Yii::$app->user->can($currentRoute)){
                                return true;
                            }
                        }
                    ]
                ]
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function actionBak()
    {

        $host = "localhost";
        $username = "root";
        $password = "";
        $database_name = "coltd";

// Get connection object and set the charset
        $conn = mysqli_connect($host, $username, $password, $database_name);
        $conn->set_charset("utf8");


// Get All Table Names From the Database
        $tables = array();
        $sql = "SHOW TABLES";
        $result = mysqli_query($conn, $sql);

        while ($row = mysqli_fetch_row($result)) {
            $tables[] = $row[0];
        }

        $sqlScript = "";
        foreach ($tables as $table) {

            // Prepare SQLscript for creating table structure
            $query = "SHOW CREATE TABLE $table";
            $result = mysqli_query($conn, $query);
            $row = mysqli_fetch_row($result);

            $sqlScript .= "\n\n" . $row[1] . ";\n\n";


            $query = "SELECT * FROM $table";
            $result = mysqli_query($conn, $query);

            $columnCount = mysqli_num_fields($result);

            // Prepare SQLscript for dumping data for each table
            for ($i = 0; $i < $columnCount; $i++) {
                while ($row = mysqli_fetch_row($result)) {
                    $sqlScript .= "INSERT INTO $table VALUES(";
                    for ($j = 0; $j < $columnCount; $j++) {
                        $row[$j] = $row[$j];

                        if (isset($row[$j])) {
                            $sqlScript .= '"' . $row[$j] . '"';
                        } else {
                            $sqlScript .= '""';
                        }
                        if ($j < ($columnCount - 1)) {
                            $sqlScript .= ',';
                        }
                    }
                    $sqlScript .= ");\n";
                }
            }

            $sqlScript .= "\n";
        }

        if (!empty($sqlScript)) {
            // Save the SQL script to a backup file
            $backup_file_name = $database_name . '_backup_' . time() . '.sql';
            $fileHandler = fopen($backup_file_name, 'w+');
            $number_of_lines = fwrite($fileHandler, $sqlScript);
            fclose($fileHandler);

            // Download the SQL backup file to the browser
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . basename($backup_file_name));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($backup_file_name));
            ob_clean();
            flush();
            readfile($backup_file_name);
            exec('rm ' . $backup_file_name);
        }


        //  $this->backup_tables('localhost','root','','coltd');
    }

    public function actionRestorepage()
    {
        return $this->render('_restore');
    }

    public function actionRestoredb()
    {
        //   $conn = mysqli_connect("localhost", "root", "", "coltd");
        $conn = \Yii::$app->db;
        $uploaded = UploadedFile::getInstanceByName('restore_file');
        if (!empty($uploaded)) {
            //print_r($uploaded);return;
            $upfiles = time() . "." . $uploaded->getExtension();
            if ($uploaded->saveAs('../web/uploads/backup/' . $upfiles)) {
                //echo "uploaded";return;
                // Validating SQL file type by extensions
                if ($uploaded->getExtension() != "sql") {
                    $response = array(
                        "type" => "error",
                        "message" => "Invalid File Type"
                    );
                } else {
                    $response = $this->restoreMysqlDB($conn, $upfiles);
                    //print_r($response);
                    \Yii::$app->session->setFlash('msg','Restore ข้อมูลสำเร็จ');
                    return $this->redirect(['dbrestore/restorepage']);

                }


            }else{
                echo "cannot save file.";
            }
        }else{
            echo "empty file.";
        }

    }

    public function restoreMysqlDB($conn, $filename)
    {
        $sql = '';
        $error = '';
        $filePath = '../web/uploads/backup/' . $filename;
        if (file_exists($filePath)) {
            // return 'has';
            $lines = file($filePath);

            foreach ($lines as $line) {

                // Ignoring comments from the SQL script
                if (substr($line, 0, 2) == '--' || $line == '') {
                    continue;
                }

                $sql .= $line;

                if (substr(trim($line), -1, 1) == ';') {
                    // $result = mysqli_query($conn, $sql);
                    $result = \Yii::$app->db->createCommand($sql)->query();
                    if (!$result) {
                        $error .= mysqli_error($conn) . "\n";
                    }
                    $sql = '';
                }
            } // end foreach

            if ($error) {
                $response = array(
                    "type" => "error",
                    "message" => $error
                );
            } else {
                $response = array(
                    "type" => "success",
                    "message" => "Database Restore Completed Successfully."
                );
            }
        } // end if file exists
        return $response;
    }

    public function actionBackuplist()
    {
        return $this->render('_backuplist');
    }

    public function actionExrestore()
    {
        $host = "localhost";
        $username = "root";
        // $password = "'" . "Ning0615410888$$" . "'";
        $password = "";
        $database_name = "vorapat";
        $date_string = time();

        $cmd = '';

        $os = php_uname();
        if (strpos($os, 'ndow') > 0) {
            $cmd = 'D:/xampp/mysql/bin/';
            $cmd .= "mysqldump -h {$host} -u {$username} {$database_name} > " . '../web/uploads/backup/' . "pc_{$date_string}_{$database_name}.sql";

        } else {
            //    $cmd ='/usr/bin/';
            $cmd = "/usr/bin/mysqldump -u {$username} -p{$password} {$database_name} > " . '../web/uploads/backup/' . "web_{$date_string}_{$database_name}.sql";
        }


        exec($cmd);

        return $this->redirect(['dbbackup/backuplist']);

    }

    public function backup_tables($host, $user, $pass, $name, $tables = '*')
    {

        $link = mysqli_connect($host, $user, $pass);
        mysqli_set_charset($link, "utf8");

        mysqli_select_db($link, $name);

        $return = '';

        //get all of the tables
        if ($tables == '*') {
            $tables = array();
            $result = mysqli_query($link, 'SHOW TABLES');
            while ($row = mysqli_fetch_row($result)) {
                $tables[] = $row[0];
            }
        } else {
            $tables = is_array($tables) ? $tables : explode(',', $tables);
        }

        //cycle through
        foreach ($tables as $table) {
            $result = mysqli_query($link, 'SELECT * FROM ' . $table);
            $num_fields = mysqli_num_fields($result);

            $return .= 'DROP TABLE ' . $table . ';';
            $row2 = mysqli_fetch_row(mysqli_query($link, 'SHOW CREATE TABLE ' . $table));
            $return .= "\n\n" . $row2[1] . ";\n\n";

            for ($i = 0; $i < $num_fields; $i++) {
                while ($row = mysqli_fetch_row($result)) {
                    $return .= 'INSERT INTO ' . $table . ' VALUES(';
                    for ($j = 0; $j < $num_fields; $j++) {
                        $row[$j] = addslashes($row[$j]);
                        $row[$j] = ereg_replace("\n", "\\n", $row[$j]);
                        if (isset($row[$j])) {
                            $return .= '"' . $row[$j] . '"';
                        } else {
                            $return .= '""';
                        }
                        if ($j < ($num_fields - 1)) {
                            $return .= ',';
                        }
                    }
                    $return .= ");\n";
                }
            }
            $return .= "\n\n\n";
        }

        //save file
        $handle = fopen('db-backup-' . time() . '-' . (md5(implode(',', $tables))) . '.sql', 'w+');
        fwrite($handle, $return);
        fclose($handle);
    }

    /**
     * Lists all Picking models.
     * @return mixed
     */
    public function actionIndex()
    {
        $model = new Modelfile();
        if ($model->load(Yii::$app->request->post())) {
            $uploaded = UploadedFile::getInstance($model, 'file');
            if (!empty($uploaded)) {
                $uploaded->saveAs(Yii::getAlias('@backend') . '/backups/' . $uploaded);
                $this->redirect(['db-manager/default']);
            }
        }

        return $this->render('index', [
            'modelfile' => $model
        ]);
    }


    public function actionDownloadbak($id)
    {
        if ($id != '') {
            $filepath = "../web/uploads/backup/" . $id;

            // Process download
            if (file_exists($filepath)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($filepath));
                flush(); // Flush system output buffer
                readfile($filepath);
                die();
            } else {
                // echo "no";return;
                http_response_code(404);
                die();
            }
        }
        return $this->redirect(['dbrestore/backuplist']);
    }

    public function actionDeletebak($id)
    {
        if ($id != '') {
            unlink('../web/uploads/backup/' . $id);
        }
        return $this->redirect(['dbmanagement/backuplist']);
    }

}
