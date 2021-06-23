<?php

namespace Salesbeat\Lib;

class Storage
{
    private static $main = null; // Объект хранилища
    private $sessionId = ''; // Id хранилища
    private $storage = []; // Хранилище

    /**
     * Storage constructor.
     */
    public function __construct()
    {
        $arCookie = WC()->session->get_session_cookie();
        if (!empty($arCookie[3])) $this->sessionId = '_salesbeat_sessid_' . $arCookie[3];

        if (empty($this->storage) && $this->sessionId)
            $this->storage = \get_transient($this->sessionId);
    }

    /**
     * @return Storage|null
     */
    public static function main()
    {
        if (is_null(self::$main))
            self::$main = new self();

        return self::$main;
    }

    /**
     * @return array
     */
    public function get()
    {
        return $this->storage;
    }

    /**
     * @param int $id
     * @return array
     */
    public function getByID($id)
    {
        return !empty($this->storage[$id]) ? $this->storage[$id] : [];
    }

    /**
     * @param int $id
     * @param array $data
     */
    public function set($id, array $data)
    {
        if ($id && is_array($data)) {
            if (empty($this->storage[$id])) $this->storage[$id] = [];
            $this->storage[$id] = $this->transform($data);
            $this->update();
        }
    }

    /**
     * @param int $id
     * @param array $data
     */
    public function append($id, array $data)
    {
        if (empty($this->storage[$id])) $this->storage[$id] = [];
        $this->storage[$id] = array_merge($this->storage[$id], $this->transform($data));
        $this->update();
    }

    public function delete()
    {
        $this->storage = [];
        $this->update();
    }

    /**
     * @param int $id
     */
    public function deleteById($id)
    {
        if (!empty($this->storage[$id])) {
            $this->storage[$id] = [];
            $this->update();
        }
    }

    /**
     * @param array $data
     * @return array
     */
    private function transform(array $data)
    {
        $arResult = [];
        foreach ($data as $key => $value)
            $arResult[strtolower($key)] = $value;

        return $arResult;
    }

    private function update()
    {
        if (!empty($this->storage)) {
            \set_transient($this->sessionId, $this->storage, 3600);
        } else {
            \delete_transient($this->sessionId);
        }

        \WC_Cache_Helper::get_transient_version('shipping', true);
    }
}