<?php

namespace Xupopter\System;

interface IProvider {
    public function crawl($path);
    public function parseItem($html);
}
