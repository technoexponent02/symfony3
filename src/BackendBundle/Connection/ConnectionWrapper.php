<?php
namespace BackendBundle\Connection;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Session\Session;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Events;
use Doctrine\DBAL\Event\ConnectionEventArgs;

/*
 * @author Dawid zulus Pakula [zulus@w3des.net]
 */
class ConnectionWrapper extends Connection
{

    const SESSION_ACTIVE_DYNAMIC_CONN = 'active_dynamic_conn';

    /**
     * @var Session
     */
    private $session;

    /**
     * @var bool
     */
    private $_isConnected = false;

    private $company_host;
    private $company_db;
    private $company_user;
    private $company_pass;

    /**
     * @param Session $sess
     */
    public function setSession(Session $sess, $company_host = '', $company_db = '', $company_user = '', $company_pass = '')
    {
        $this->session = $sess;
        $this->company_host = $company_host;
        $this->company_db = $company_db;
        $this->company_user = $company_user;
        $this->company_pass = $company_pass;
    }

    public function forceSwitch($dynamic_hostName = '127.0.0.1', $dynamic_dbName, $dynamic_dbUser, $dynamic_dbPassword)
    {
        if($this->session->has(self::SESSION_ACTIVE_DYNAMIC_CONN))
        {
            $current = $this->session->get(self::SESSION_ACTIVE_DYNAMIC_CONN);
            if($current['dynamic_dbName'] === $dynamic_dbName)
            {
                return;
            }
        }

        $this->session->set(self::SESSION_ACTIVE_DYNAMIC_CONN, [
            'dynamic_hostName' => $dynamic_hostName,
            'dynamic_dbName' => $dynamic_dbName,
            'dynamic_dbUser' => $dynamic_dbUser,
            'dynamic_dbPassword' => $dynamic_dbPassword,
        ]);

        if($this->isConnected())
        {
            $this->close();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function connect()
    {
        if($this->session)
        {
            $myVar = $this->session->get(self::SESSION_ACTIVE_DYNAMIC_CONN, false);
            if($myVar)
            {
                $company_db_cred = $this->session->get(self::SESSION_ACTIVE_DYNAMIC_CONN);
                $this->company_host = $company_db_cred['dynamic_hostName'];
                $this->company_db = $company_db_cred['dynamic_dbName'];
                $this->company_user = $company_db_cred['dynamic_dbUser'];
                $this->company_pass = $company_db_cred['dynamic_dbPassword'];
            }
        }
            

        if($this->isConnected())
        {
            return true;
        }

        $driverOptions = isset($params['driverOptions']) ? $params['driverOptions'] : array();

        $params = $this->getParams();
        /*$realParams = $this->session->get(self::SESSION_ACTIVE_DYNAMIC_CONN);*/
        $params['host'] = $this->company_host;
        $params['dbname'] = $this->company_db;
        $params['user'] = $this->company_user;
        $params['password'] = $this->company_pass;

        $this->_conn = $this->_driver->connect($params, $params['user'], $params['password'], $driverOptions);

        if($this->_eventManager->hasListeners(Events::postConnect))
        {
            $eventArgs = new ConnectionEventArgs($this);
            $this->_eventManager->dispatchEvent(Events::postConnect, $eventArgs);
        }

        $this->_isConnected = true;

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function isConnected()
    {
        return $this->_isConnected;
    }

    /**
     * {@inheritDoc}
     */
    public function close()
    {
        if($this->isConnected())
        {
            parent::close();
            $this->_isConnected = false;
        }
    }
}