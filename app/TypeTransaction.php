<?php

namespace App;

enum TypeTransaction: string
{
    case DEPOT = 'depot';
    case RETRAIT = 'retrait';
}
