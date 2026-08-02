<?php

namespace App\Support;

/** 统一业务返回结构（对标 JYS Result） */
class Result
{
    public static function ok(mixed $data = null, string $msg = 'ok'): array
    {
        return ['ok' => true, 'msg' => $msg, 'data' => $data];
    }

    public static function fail(string $msg, mixed $data = null): array
    {
        return ['ok' => false, 'msg' => $msg, 'data' => $data];
    }
}
