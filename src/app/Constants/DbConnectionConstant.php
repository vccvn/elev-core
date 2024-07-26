<?php

namespace Gomee\Constants;


class DbConnectionConstant
{
    const SQL   = 'pgsql';
    const NOSQL = 'mongodb';
    const ID = '_id';

    const ALL
        = [
            self::SQL,
            self::NOSQL
        ];
}
