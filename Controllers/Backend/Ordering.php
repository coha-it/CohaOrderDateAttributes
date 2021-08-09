<?php

use Shopware\Components\CSRFWhitelistAware;

class Shopware_Controllers_Backend_Ordering extends \Enlight_Controller_Action implements CSRFWhitelistAware
{
    public function indexAction()
    {
        // Get Config
        $config = $this->loadConfig();

        // Database
        $queryBuilder = $this->container->get('dbal_connection')->createQueryBuilder();
        $table = 's_filter_values';

        // Config ID
        $configId = $config['id'];

        if(!$configId) {
            die("Error! No Config-ID");
        }

        // DB-Select
        try {
            $queryBuilder->select('*')
                ->from($table)
                ->where('optionID = :id')
                ->setParameter('id', $configId);
            $data = $queryBuilder->execute()->fetchAll();

            // Repositioning
            foreach ($this->sortArrayByDate($data) as $key => $attribute) {
                // DB-Update
                $sql = "UPDATE $table SET position = :position WHERE id = :id";

                $connection = $this->container->get('dbal_connection');
                $statement = $connection->prepare($sql);
                $statement->execute([
                    'position' => $key, 
                    'id' => $attribute['id']
                ]);
            }

            echo 'Sort successfull for entries: '. count($data);
        } catch (\Throwable $error) {
            echo $error;
        }

    }

    public function sortArrayByDate($data) {
        $arr = Array();
        foreach ($data as $attribute) {
            $sDate = $attribute['value'];
            if($this->checkIsAValidDate($sDate)) {
                $attribute['value'] = date("Y-m-d", strtotime($sDate));
            }
            array_push($arr, $attribute);
        }
        usort($arr, function ($item1, $item2) {
            return $item1['value'] <=> $item2['value'];
        });
        return $arr;
    }

    public function loadConfig()
    {
        $this->container->get('front')->Plugins()->ViewRenderer()->setNoRender();
        return $this->container->get('shopware.plugin.config_reader')->getByPluginName('CohaOrderDateAttributes');
    }

    public function checkIsAValidDate($myDateString)
    {
        return (bool)strtotime($myDateString);
    }

    // public function  validateDate($date, $format = 'd.m.Y')
    // {
    //     $d = DateTime::createFromFormat($format, $date);
    //     // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
    //     return $d && $d->format($format) === $date;
    // }

    /**
     * Returns a list with actions which should not be validated for CSRF protection
     *
     * @return string[]
     */
    public function getWhitelistedCSRFActions()
    {
        return ['index'];
    }
}
