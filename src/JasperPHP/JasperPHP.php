<?php
namespace JasperPHP;

class JasperPHP
{
    protected $executable;
    protected $theCommand;
    protected $redirectOutput;
    protected $background;
    protected $windows = false;
    protected $formats;
    protected $resourceDirectory;

    function __construct()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
            $this->windows = true;
        $this->jasperPhpInit();
    }

    public function jasperPhpInit($params = [])
    {
        $this->initParams($params);

        return $this;
    }

    public function initParams(array $params = [])
    {
        if (!empty($params)) {
            extract($params);
        }

        $this->executable = isset($executable) ? $executable : __DIR__ . "/../JasperStarter/bin/jasperstarter";
        $this->formats = isset($formats) ? $formats : ['pdf', 'rtf', 'xls', 'xlsx', 'docx', 'odt', 'ods', 'pptx',
            'csv', 'html', 'xhtml', 'xml', 'jrprint'];
        $this->resourceDirectory = isset($resourceDirectory) ? $resourceDirectory : __DIR__ . "/../../../../../";
    }

    public static function __callStatic($method, $parameters)
    {
        $jasperPhpObject = new self();

        $method = "jasperPhp" . ucwords($method);

        if (!method_exists($jasperPhpObject, $method)) {
            throw new \Exception("Invalid method!");
        }

        return call_user_func_array([$jasperPhpObject, $method], $parameters);
    }

    public function compile($input_file, $output_file = false, $background = true, $redirect_output = true)
    {
        if (is_null($input_file) || empty($input_file))
            throw new \Exception("No input file", 1);

        $command = $this->executable;

        $command .= " cp ";

        $command .= $input_file;

        if ($output_file !== false)
            $command .= " -o " . $output_file;

        $this->redirectOutput = $redirect_output;
        $this->background = $background;
        $this->theCommand = $command;

        return $this;
    }

    public function process($input_file, $output_file = false, $format = ["pdf"], $parameters = [], $db_connection = [], $background = true, $redirect_output = true)
    {
        if (is_null($input_file) || empty($input_file))
            throw new \Exception("No input file", 1);

        if (is_array($format)) {
            foreach ($format as $key) {
                if (!in_array($key, $this->formats))
                    throw new \Exception("Invalid format!", 1);
            }
        } else {
            if (!in_array($format, $this->formats))
                throw new \Exception("Invalid format!", 1);
        }

        $command = $this->executable;

        $command .= " pr ";

        $command .= $input_file;

        if ($output_file !== false)
            $command .= " -o " . $output_file;

        if (is_array($format))
            $command .= " -f " . join(" ", $format);
        else
            $command .= " -f " . $format;

        // Resources dir
        $command .= " -r " . $this->resourceDirectory;

        if (count($parameters) > 0) {
            $command .= " -P";
            foreach ($parameters as $key => $value) {
                $command .= " " . $key . "=" . $value;
            }
        }

        if (count($db_connection) > 0) {
            $command .= " -t " . $db_connection['driver'];
            $command .= " -u " . $db_connection['username'];

            if (isset($db_connection['password']) && !empty($db_connection['password']))
                $command .= " -p " . $db_connection['password'];

            if (isset($db_connection['host']) && !empty($db_connection['host']))
                $command .= " -H " . $db_connection['host'];

            if (isset($db_connection['database']) && !empty($db_connection['database']))
                $command .= " -n " . $db_connection['database'];

            if (isset($db_connection['port']) && !empty($db_connection['port']))
                $command .= " --db-port " . $db_connection['port'];

            if (isset($db_connection['jdbc_driver']) && !empty($db_connection['jdbc_driver']))
                $command .= " --db-driver " . $db_connection['jdbc_driver'];

            if (isset($db_connection['jdbc_url']) && !empty($db_connection['jdbc_url']))
                $command .= " --db-url " . $db_connection['jdbc_url'];
        }

        $this->redirectOutput = $redirect_output;
        $this->background = $background;
        $this->theCommand = $command;

        return $this;
    }

    public function output()
    {
        return $this->theCommand;
    }

    public function mustRun($run_as_user = false)
    {
        $this->background = false;
        $this->execute($run_as_user);

        return $this;
    }

    private function execute($run_as_user = false)
    {
        if ($this->redirectOutput && !$this->windows)
            $this->theCommand .= " > /dev/null 2>&1";

        if ($this->background && !$this->windows)
            $this->theCommand .= " &";

        if ($run_as_user !== false && strlen($run_as_user > 0) && !$this->windows)
            $this->theCommand = "su -u " . $run_as_user . " -c \"" . $this->theCommand . "\"";

        $output = [];
        $return_var = 0;

        exec($this->theCommand, $output, $return_var);

        if ($return_var != 0)
            throw new \Exception("There was and error executing the report! Time to check the logs!", 1);

        return $output;
    }

    public function run($run_as_user = false)
    {
        $this->background = true;
        $this->execute($run_as_user);

        return $this;
    }
}
