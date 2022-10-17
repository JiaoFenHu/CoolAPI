<?php
declare(strict_types=1);

namespace repository\basic;

use repository\exception\CoolException;
use repository\Instance;

class Configure extends Instance
{

    static array $configs = [];

    /**
     * 获取配置文件内容
     * @param $confFileName
     * @return mixed
     * @throws CoolException
     */
    private function loadConfig($confFileName)
    {
        $configFilePath = CONFIG_DIR . $confFileName . '.php';
        if (!file_exists($configFilePath)) {
            throw new CoolException("未知的配置文件！");
        }

        if (!array_key_exists($confFileName, self::$configs)) {
            self::$configs[$confFileName] = require($configFilePath);
        }
        return self::$configs[$confFileName];
    }

    /**
     * 获取配置
     * @param string $confFileName
     * @param string $confName
     * @return mixed
     * @throws CoolException
     */
    private function getConfigData(string $confFileName, string $confName)
    {
        $confData = $this->loadConfig($confFileName);
        $confNames = explode('.', $confName);
        switch (count($confNames)) {
            case 1:
                $nameData = $confData[$confNames[0]];
                break;
            case 2:
                $nameData = $confData[$confNames[0]][$confNames[1]];
                break;
            default:
                $nameData = $confData;
                foreach ($confNames as $name) {
                    $nameData = $nameData[$name];
                }
                break;
        }
        return $nameData;
    }

    /**
     * 获取app配置信息
     * @param string $confName
     * @param null $default
     * @return mixed
     * @throws CoolException
     */
    final public static function app(string $confName, $default = null)
    {
        $confData = Configure::getInstance()->getConfigData('app', $confName);
        if (is_null($confData) && !is_null($default)) {
            return $default;
        }
        return $confData;
    }

    /**
     * 可以获取自定义指定的配置
     * @param string $confName
     * @param null $default
     * @return mixed
     * @throws CoolException
     */
    final public static function config(string $confName, $default = null)
    {
        $confNames = explode('.', $confName);
        $confFileName = array_shift($confNames);
        $confName = implode('.', $confNames);

        $confData = Configure::getInstance()->getConfigData($confFileName, $confName);
        if (is_null($confData) && !is_null($default)) {
            return $default;
        }
        return $confData;
    }

}