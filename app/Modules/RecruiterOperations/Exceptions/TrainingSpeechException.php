<?php

namespace App\Modules\RecruiterOperations\Exceptions;

use RuntimeException;

/**
 * A speech provider could not produce audio. The message is safe to show.
 */
class TrainingSpeechException extends RuntimeException {}
