<?php
namespace JasperPHP;

class CommandBuilder
{
    private $command = '';
    private $dataSourceType = '';
    private $parameters = [];
    private $tempFile = false;

    public function __construct($executable)
    {
        $this->command = $executable;
    }

    public function getTempFile()
    {
        return $this->tempFile;
    }


    public function input($filename)
    {
        if (is_null($filename) || empty($filename))
            throw new \Exception("No input file", 1);

        $this->command .=" pr ".$filename;

        return $this;
    }

    public function output($filename)
    {
        if ($filename !== false)
            $this->command .= " -o " . $filename;

        return $this;
    }

    public function format($format = ["pdf"])
    {
        if (is_array($format))
            $this->command .= " -f " . join(" ", $format);
        else
            $this->command .= " -f " . $format;
        return $this;
    }

    public function resourcePath($path)
    {
        if(!empty($path) && file_exists($path)) {
            $this->command .= " -r " . $path;
        }

        return $this;
    }

    public function dataSource($type)
    {
        if(!empty($type) && in_array($type, ['mysql','xml','csv','json'])) {
            $this->command .= " -t " . $type;
            $this->dataSourceType = $type;
        }

        return $this;
    }

    public function addParams($parameters)
    {
        foreach($parameters as $key=>$value)
        {
            $this->addParam($key, $value);
        }

        return $this;
    }

    public function addParam($key, $value)
    {
        $this->parameters[$key] = $value;

        return $this;
    }

    public function query($params= [])
    {
        if(empty($params)) {
            return $this;
        }
        extract($params);
        switch($this->dataSourceType) {
            case 'mysql':
                if (isset($username) && !empty($username))
                    $this->command .= " -u " . $username;

                if (isset($password) && !empty($password))
                    $this->command .= " -p " . $password;

                if (isset($host) && !empty($host))
                    $this->command .= " -H " . $host;

                if (isset($database) && !empty($database))
                    $this->command .= " -n " . $database;

                if (isset($port) && !empty($port))
                    $this->command .= " --db-port " . $port;

                if (isset($jdbcDriver) && !empty($jdbcDriver))
                    $this->command .= " --db-driver " . $jdbcDriver;

                if (isset($jdbc_url) && !empty($jdbc_url))
                    $this->command .= " --db-url " . $jdbc_url;
                break;
            case 'xml':
                if(isset($query) && !empty($query))
                    $this->command .=" --xml-xpath ".$query;

                if(isset($xmlUrl) && !empty($xmlUrl)) {
                    $dataFile = $this->tempFile = '/tmp/xml-'.time().".xml";
                    @file_put_contents($dataFile, @file_get_contents($xmlUrl));
                }

                if(isset($xmlString) && !empty($xmlString)) {
                    $dataFile = $this->tempFile = '/tmp/xml-'.time().".xml";
                    @file_put_contents($dataFile, $xmlString);
                }

                break;
            case 'json':
                if(isset($query) && !empty($query))
                    $this->command .=" --json-query ".$query;
                break;
        }

        if(isset($dataFile) && !empty($dataFile)) {
            $this->command .= " --data-file ".$dataFile;
        }

        return $this;
    }



    public function getCommand()
    {
        if(count($this->parameters) > 0) {
            $this->command .=" -P";
            foreach ($this->parameters as $key => $value) {
                $this->command .= " " . $key . "=\"" . $value."\"";
            }
        }
        return $this->command;
    }
}