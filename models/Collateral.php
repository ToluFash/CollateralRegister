<?php
namespace models;

require_once $_SERVER['DOCUMENT_ROOT']."/controller/Database.php";

use controller\Database;

class Collateral
{
    private $title;
    private $firstName;
    private $lastname;
    private $middlename;
    private $uploader;
    private $branch;
    private $comments;
    private $status;
    private $date;
    private $timestamp;
    private $files;
    private $queries;

    /**
     * Collateral constructor.
     * @param $title
     * @param $firstName
     * @param $lastname
     * @param $middlename
     * @param $uploader
     * @param $branch
     * @param $comments
     * @param $date
     * @param $files
     */
    public function __construct($title, $firstName, $lastname, $middlename, $uploader, $branch, $comments, $files,$timestamp1)
    {
        $this->title = $title;
        $this->firstName = $firstName;
        $this->lastname = $lastname;
        $this->middlename = $middlename;
        $this->uploader = $uploader;
        $this->branch = $branch;
        $this->comments = $comments;
        $tz = 'Africa/Lagos';
        $timestamp = time();
        $dt = new \DateTime("now", new \DateTimeZone($tz));
        $dt->setTimestamp($timestamp);
        $this->date = $dt->format("Y-m-d H:i:s");
        $this->files = $files;
        $this->status = 0;
        $this->release = 0;
        $this->timestamp = $timestamp1;
        $this->queries = [];
    }

    public static function fromJson($content,$title)
    {
        $coll = new Collateral($content["title"], $content["first_name"], $content["last_name"], $content["middle_name"],
            $content["uploader"], $content["branch"], $content["comments"], $content["files"],$title);
        return $coll;
    }


    public static function getAllCollaterals()
    {
        $sql = "SELECT * FROM collaterals";
        $result = Database::select($sql);

        return ["collaterals" => array_reverse($result)];

    }
    public static function returnOne($title)
    {
        $sql = "SELECT * FROM collaterals
WHERE id='$title'";
        $result = Database::select($sql);

        return $result[0];

    }

    public static function returnOneByTitle($title)
    {
        $sql = "SELECT * FROM collaterals
WHERE title='$title'";
        $result = Database::select($sql);
        return $result[0];

    }
    public static function changeStatus($id, $status){

        $sql = "UPDATE collaterals
        SET status='$status'
        WHERE id='$id'";
        Database::selectRC($sql);

    }

    public static function requestRelease($id, $release){

        $sql = "UPDATE collaterals
        SET rrelease='$release'
        WHERE id='$id'";
        $result = Database::selectRC($sql);
    }
    public static function requestReEnact($id, $release){

        $sql = "UPDATE collaterals
        SET reenact='$release'
        WHERE id='$id'";
        $result = Database::selectRC($sql);
    }

    public function persist()
    {
        array_push($this->queries, "INSERT INTO collaterals (title,first_name,last_name, middle_name, uploader,branch,comments, date,files,status,rrelease,cctimestamp) 
VALUES ('$this->title','$this->firstName','$this->lastname','$this->middlename','$this->uploader','$this->branch','$this->comments','$this->date','$this->files','$this->status','$this->release','$this->timestamp')");
    }

    public static function getAllUsers(){
        $sql = "SELECT * FROM collaterals";
        $result = Database::select($sql);

        return ["users" =>$result];

    }
    public function flush()
    {
        try {
            foreach ($this->queries as $query)
                Database::executeQuery($query);

        } catch (\PDOException $e) {
            throw $e;
        }


    }
}