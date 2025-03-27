<?php
trait IX_WPB_Singleton {
    private static $instance;

    final public static function instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init();
    }

    abstract protected function init();
}