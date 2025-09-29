<?php

namespace Juniyasyos\FilamentLaravelBackup\Enums;

enum BackupStatus: string
{
    case QUEUED = 'queued';
    case RUNNING = 'running';
    case SUCCESS = 'success';
    case FAILED = 'failed';
}
