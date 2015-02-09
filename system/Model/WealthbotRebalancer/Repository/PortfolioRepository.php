<?php
namespace Model\WealthbotRebalancer\Repository;

use Model\WealthbotRebalancer\Client;
use Model\WealthbotRebalancer\Portfolio;

require_once(__DIR__ . '/../../../AutoLoader.php');
\AutoLoader::registerAutoloader();

class PortfolioRepository extends BaseRepository {

    protected function getOptions()
    {
        return array(
            'table_name' => self::TABLE_CLIENT_PORTFOLIO,
            'model_name' => 'Model\WealthbotRebalancer\Portfolio'
        );
    }

    /**
     * Find active portfolio by client
     *
     * @param Client $client
     * @return Portfolio|null
     */
    public function findPortfolioByClient(Client $client)
    {
        $sql = "SELECT cp.portfolio_id as id FROM {$this->table} cp
                WHERE cp.is_active = :isActive AND cp.client_id = :clientId";

        $parameters = array(
            'isActive' => true,
            'clientId' => $client->getId()
        );

        $result = $this->mySqlDB->query($sql, $parameters);
        $collection = $this->bindCollection($result);

        $portfolio = $collection->first();

        if ($portfolio) {
            $client->setPortfolio($portfolio);

            return $portfolio;
        }

        return null;
    }

    /**
     * Get portfolio values from db and update portfolio object
     *
     * @param Client $client
     */
    public function loadPortfolioValues(Client $client)
    {
        $sql = "SELECT * FROM " . self::TABLE_CLIENT_PORTFOLIO_VALUE . " as cpv
                LEFT JOIN {$this->table} cp ON (cpv.client_portfolio_id = cp.id)
                LEFT JOIN ".self::TABLE_USER." u ON u.id = cp.client_id
                WHERE cp.portfolio_id = :portfolio_id AND u.id = :clientId
                ORDER BY cpv.date desc LIMIT 1";

        $pdo = $this->mySqlDB->getPdo();

        $portfolio = $client->getPortfolio();

        $statement = $pdo->prepare($sql);
        $statement->execute(array(
            'portfolio_id' => $portfolio->getId(),
            'clientId' => $client->getId()
        ));

        $data = $statement->fetch(\PDO::FETCH_ASSOC);

        if ($data) {
            $portfolio->setTotalValue(isset($data['total_value']) ? $data['total_value'] : 0);
            $portfolio->setTotalInSecurities(isset($data['total_in_securities']) ? $data['total_in_securities'] : 0);
            $portfolio->setTotalCashInAccounts(isset($data['total_cash_in_accounts']) ? $data['total_cash_in_accounts'] : 0);
            $portfolio->setTotalCashInMoneyMarket(isset($data['total_cash_in_money_market']) ? $data['total_cash_in_money_market'] : 0);
            $portfolio->setSasCash(isset($data['sas_cash']) ? $data['sas_cash'] : 0);
            $portfolio->setCashBuffer(isset($data['cash_buffer']) ? $data['cash_buffer'] : 0);
            $portfolio->setBillingCash(isset($data['billing_cash']) ? $data['billing_cash'] : 0);
        }
    }
}