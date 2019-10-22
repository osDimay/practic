<?php

namespace stormProject;

class CsvErrClass
{
    public $lineNum;
    public $errCode;
    public $description;

    function __construct($errLine, $errNum, $Code = 400)
    {
        $this->lineNum = $errLine;

        switch($errNum){
            case 0:
                $this->errCode = $Code;
                $this->description = "Not numeric data in id field";
                break;
            case 1:
                $this->errCode = $Code;
                $this->description = "id <= 0";
                break;
            case 2:
                $this->errCode = $Code;
                $this->description = "Not numeric data in parent_id field";
                break;
            case 3:
                $this->errCode = $Code;
                $this->description = "parent_id < 0";
                break;
            case 4:
                $this->errCode = $Code;
                $this->description = "Field name doesn't exist";
                break;
            case 5:
                $this->errCode = $Code;
                $this->description = "Field name length > 255";
                break;
            default:
                $this->errCode = $Code;
                $this->description = "Unknown problem";
        }
    }
}
