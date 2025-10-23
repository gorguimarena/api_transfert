<?php

namespace App;

enum TypeTransaction: string
{
    case CREDIT = 'credit';
    case DEBIT = 'debit';
}
