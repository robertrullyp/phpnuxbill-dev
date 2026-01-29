<?php

class html_php
{

    public function getWidget($data = null)
    {
        global $ui;
        $ui->assign('card_header', $data['title']);
        ob_start();
        try {
            echo $data['content'];
        } catch (Throwable $e) {
            echo $e->getMessage();
        }
        $content = ob_get_clean();
        return $content;
    }
}