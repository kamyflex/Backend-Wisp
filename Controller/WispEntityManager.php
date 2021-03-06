<?php

// require_once ('WispConnectionManager.php');

Class WispEntityManager
{
    protected static $singleton;
    protected $entities;
    protected $isScaffoldMode;

    // Constructor
    protected function __construct()
    {
        $this->entities = array();
    }

    // Get Singleton
    public static function Get() : WispEntityManager
    {
        if (empty(WispEntityManager::$singleton)) {
            WispEntityManager::$singleton = new WispEntityManager ();

            $ini = dirname(__FILE__, 2) . '/Model/App.ini';
            
            $iniArray = parse_ini_file($ini, true);
            $sectionName = "Application";
            WispEntityManager::$singleton->isScaffoldMode = $iniArray[$sectionName]['scaffolding'];
        }

        return WispEntityManager::$singleton;
    }

    // Register an entity
    public function RegisterEntity(WispEntity $ParamEntity)
    {
        // Scaffold the Entity
        array_push($this->entities, $ParamEntity);

        if ($this->isScaffoldMode)
        {
            $doesTableExists = WispConnectionManager::Get()->CheckIfTableExists($ParamEntity->GetTableName());
            if (!$doesTableExists) WispConnectionManager::Get()->CreateTable($ParamEntity->GetTableName(), true);
        }

        // Scaffold Properties
        $properties = $ParamEntity->GetCopyOfProperties();

        foreach ($properties as $key => $value) {
            if ($this->isScaffoldMode)
                $value->Scaffold();
        }
    }

    // ...
    public function GetEntityCount() : int
    {
        return sizeof($this->entities);
    }

    // Get a list of entities with their glyph paths in json format, usefull to generate a main menu
    public function GetJson(string $ParamPrivilege = '') : string
    {
        $array_entities = array();
        for ($i = 0; $i < count($this->entities); $i++) {
            $array_entities[$this->entities[$i]->GetName()] = $this->entities[$i]->GetJsonArray();
        }

        $array_all = array
        (
            'Type' => 'MainMenu',
            'Entities' => $array_entities
        );

        // $array_all = array($array_meta,$array_entities);

        return json_encode($array_all);
    }

    // ...
    public function GetEntityByName(string $ParamEntityName) : WispEntity
    {
        for ($i = 0; $i < count($this->entities); $i++) {
            if ($this->entities[$i]->GetName() == $ParamEntityName) {
                return $this->entities[$i];
            }
        }

        return null;
    }
}

?>