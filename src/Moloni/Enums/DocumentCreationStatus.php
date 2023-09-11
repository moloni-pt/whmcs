<?php

namespace Moloni\Enums;

class DocumentCreationStatus
{
    const MASS_PAY = -1;
    const INSERTED_AS_DRAFT = 0;
    const INSERTED_AS_CLOSED_AND_SENT = 1;
    const INSERTED_AS_CLOSED = 2;
    const INSERTED_WITH_ERROR = 3;
    const DISCARDED = 4;
    const ERROR_CREATING = 5;
}
