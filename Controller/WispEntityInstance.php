<?php

	class WispEntityInstance extends WispEntity
	{
		// Protected variables
		Protected $ID;
		protected $entityID;
		protected $versionID;
		protected $isLast;
		protected $timeStamp;
		protected $uid;
		protected $isDeleted;

		// Public variables

		// Properties

		// Methodes
		function __Construct (WispEntity $ParamEntity)
		{
			// TODO : Find a way to clone $ParamEntity and return the cloned object as WispEntityInstance
			$this->entityName = $ParamEntity->GetEntityName();
			$this->properties = $ParamEntity->GetCopyOfProperties();
			$this->displayName = $ParamEntity->GetDisplayName();
			$this->glyphName = $ParamEntity->GetGlyphName();
			$this->displayShortcut = $ParamEntity->GetIfDisplayShortcut();
			$this->predefinedList = $ParamEntity->GetIsPredefinedList();
			$this->quickSearchProperty = $ParamEntity->GetQuickSearchProperty();
			$this->primaryPropertyName = $ParamEntity->GetPrimaryPropertyName();
			$this->secondaryPropertyName = $ParamEntity->GetSecondaryPropertyName();
			$this->thirdiaryPropertyName = $ParamEntity->GetThirdiaryPropertyName();

			// Change the parent of the properties + Update property info
    		for ($i = 0; $i < count($this->properties); $i++)
            {
                $this->properties[$i]->SetParentEntity($this);
                $this->properties[$i]->GenerateDefaultValue();
            }
		}

        // ...
        function GetID ()
        {
            return $this->ID;
        }

        // ...
        function GetEntityID ()
        {
            return $this->entityID;
        }

        // ...
        function GetVersionID ()
        {
            return $this->versionID;
        }

    	// ...
    	function ChangeID (string $ParamID)
    	{
    		$this->ID = $ParamID;
    	}

    	// ...
    	function ChangeEntityID (string $ParamID)
    	{
    		$this->entityID = $ParamID;
    	}

    	// ...
    	function ChangeVersionID (string $ParamID)
    	{
    		$this->versionID = $ParamID;
    	}

    	// ...
    	function AddToDb()
        {
        	$entityId = (int)$this->GetLastEntityID() + 1;
        	$versionId = '1';
        	$isLast = '1';
        	$timeStamp = '1970-01-01 00:00:00';
        	$isDeleted = '0';

            if (isset($_SESSION["uid"]))
            {
                $uid = $_SESSION["uid"];
            }
            else
            {
                $uid = 0;
                // TODO : Issue a warning
            }
            

        	// Create columns and values arrays 
        	$tmpColumns = array();
        	$tmpValues = array();

        	// ENTITY_ID
        	array_push($tmpColumns, 'ENTITY_ID');
        	array_push($tmpValues, $entityId);

        	// VERSION_ID
        	array_push($tmpColumns, 'VERSION_ID');
        	array_push($tmpValues, $versionId);

        	// IS_LAST
        	array_push($tmpColumns, 'IS_LAST');
        	array_push($tmpValues, $isLast);

        	// DTC
        	array_push($tmpColumns, 'DTC');
        	array_push($tmpValues, $timeStamp);

        	// UID
        	array_push($tmpColumns, 'UID');
        	array_push($tmpValues, $uid);

        	// IS_DELETED
        	array_push($tmpColumns, 'IS_DELETED');
        	array_push($tmpValues, $isDeleted);

        	// PROPERTIES
        	for ($i = 0; $i < count($this->properties); $i++)
        	{
    			if ($this->properties[$i]->IsUnifield())
    			{
    				array_push($tmpColumns, $this->properties[$i]->GetDbColumnName());
    				array_push($tmpValues, $this->properties[$i]->GetValue());
    			}
        	}

			// Insert !
        	return WispConnectionManager::Get()->ExecuteInsert($this->GetTableName(), $tmpColumns, $tmpValues);
        }

        // ...
        function AddNewVersionToDb ()
        {
        	// echo "ID = " . $this->ID;
        	$newVersionID = (int)$this->GetLastVersionByID($this->ID) + 1;
        	$this->entityID = $this->GetEntityIDFromID($this->ID);
        	$this->versionID = (string)$newVersionID; // i think its not necessary to cast it to a string
        	
        	// ------------------------------------------------------------------------------------------------------
        	// $entityId = '0';
        	// $versionId = '1';
        	$isLast = '1';
        	$timeStamp = '1970-01-01 00:00:00';
        	$uid = $_SESSION["uid"];
        	$isDeleted = '0';

        	// Create columns and values arrays 
        	$tmpColumns = array();
        	$tmpValues = array();

        	// ENTITY_ID
        	array_push($tmpColumns, 'ENTITY_ID');
        	array_push($tmpValues, $this->entityID);

        	// VERSION_ID
        	array_push($tmpColumns, 'VERSION_ID');
        	array_push($tmpValues, $this->versionID);

        	// IS_LAST
        	array_push($tmpColumns, 'IS_LAST');
        	array_push($tmpValues, $isLast);

        	// DTC
        	array_push($tmpColumns, 'DTC');
        	array_push($tmpValues, $timeStamp);

        	// UID
        	array_push($tmpColumns, 'UID');
        	array_push($tmpValues, $uid);

        	// IS_DELETED
        	array_push($tmpColumns, 'IS_DELETED');
        	array_push($tmpValues, $isDeleted);

        	// PROPERTIES
        	for ($i = 0; $i < count($this->properties); $i++)
        	{
    			if ($this->properties[$i]->IsUnifield())
                {        
                    array_push($tmpColumns, $this->properties[$i]->GetDbColumnName());
    			    array_push($tmpValues, $this->properties[$i]->GetValue());
                }
        	}

			// Insert !
        	$lastInsertID = WispConnectionManager::Get()->ExecuteInsert($this->GetTableName(), $tmpColumns, $tmpValues);
        	
        	// set as last version
        	$q = "UPDATE " . $this->GetTableName() . " SET IS_LAST = 0 WHERE ENTITY_ID = " . $this->entityID . " AND ID != " . $lastInsertID . 
        	";";

        	WispConnectionManager::Get()->ExecuteQuery($q);
        }

        // ...
        function LoadFromDb(string $ParamId)
        {
            $q = 'SELECT * FROM ' . $this->GetTableName() . ' WHERE ID="' . $ParamId . '";';

        	$result = WispConnectionManager::Get()->OpenQuery($q);

				$this->ID = $result->GetColumnValue('ID');
				$this->entityID = $result->GetColumnValue('ENTITY_ID');
				$this->versionID = $result->GetColumnValue('VERSION_ID');
				$this->isLast = $result->GetColumnValue('IS_LAST');
				$this->timeStamp = $result->GetColumnValue('DTC');
				$this->uid = $result->GetColumnValue('UID');
				$this->isDeleted = $result->GetColumnValue('IS_DELETED');

				

				for ($i = 0; $i < count($this->properties); $i++)
				{
					
					// $this->properties[$i]->SetValue($result->GetColumnValue($this->properties[$i]->GetDbColumnName()));
					$this->properties[$i]->SetValueFromDb($result);
				}


        }

        function MarkAsDeleted ()
        {
            if (is_null($this->entityID))
            {
                // Get Entity ID from DB
                // Update IS_DELETED for all the versions
            }
            else
            {
                $q = "UPDATE entity_" . $this->entityName . " SET IS_DELETED='1' WHERE ENTITY_ID ='" . $this->entityID . "';";
                // echo $q;
                WispConnectionManager::Get()->ExecuteQuery($q);
            }
        }


		function Duplicate ()
		{
			// Duplicate in the table with a new ID and ENTITY_ID
		}

		function GetJson (string $ParamPrivilege = '')
		{
            return json_encode($this->GetJsonArray());
		}

        // ...
        function GetJsonArray (string $ParamPrivilege = '')
        {
            $array_meta = array
            (
                'Type' => 'EntityInstance'
            );

            $array_basic = array
            (
                'EntityName' => $this->GetName(),
                'EntityLabel' => $this->GetDisplayName(),
                'ID' => $this->ID,
                'entityID' => $this->entityID,
                'versionID' => $this->versionID,
                'isLast' => $this->isLast,
                'timeStamp' => $this->timeStamp,
                'uid' => $this->uid,
                'isDeleted' => $this->isDeleted,
                '1' => $this->primaryPropertyName,
                '2' => $this->secondaryPropertyName,
                '3' => $this->thirdiaryPropertyName
            );

            $array_properties = array ();

            // echo "INSTANCE OF : " . $this->GetName();
            // echo "<br/>";
            for ($i = 0; $i < count($this->properties); $i++)
            {
                $array_properties[$this->properties[$i]->GetName()] = $this->properties[$i]->GetJsonArray();
                // echo "P";
            }

            $array = array($array_meta, $array_basic, $array_properties);

            return $array;
        }

		// ...
		function GetJsonWithLabels (string $ParamPrivilege = '')
		{
			$array_meta = array
			(
				'Type' => 'EntityInstance'
			);

			$array_basic = array
			(
				'EntityName' => $this->GetName(),
				'EntityLabel' => $this->GetDisplayName(),
				'ID' => $this->ID,
				'entityID' => $this->entityID,
				'versionID' => $this->versionID,
				'isLast' => $this->isLast,
				'timeStamp' => $this->timeStamp,
				'uid' => $this->uid,
				'isDeleted' => $this->isDeleted
			);

			$array_properties = array ();
			for ($i = 0; $i < count($this->properties); $i++)
			{
				$array_properties[$this->properties[$i]->GetName()] = $this->properties[$i]->GetValue();
			}

			$array = array($array_meta, $array_basic, $array_properties);

			return json_encode($array);
		}
	}

?>