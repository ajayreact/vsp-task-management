<?php

namespace App\Modules\RecruiterOperations\Console;

use App\Modules\RecruiterOperations\Models\TrainingCourse;
use App\Modules\RecruiterOperations\Models\TrainingTrack;
use Database\Seeders\RecruiterOperations\TrainingContent\RecruiterTrainingContent;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Places the four OPT Recruiter courses in the OPT Recruiter training track.
 * Only the course's track changes: versions, lessons and assignments are
 * untouched. Courses already in another track are left alone and reported.
 */
class AssignOptCoursesToTrack extends Command
{
    protected $signature = 'recruiter:training-tracks
        {--dry-run : Show which courses would move without saving anything}';

    protected $description = 'Place the four OPT Recruiter courses in the OPT Recruiter training track';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $track = TrainingTrack::query()->where('slug', TrainingTrack::OPT_RECRUITER)->first();

        if ($track === null) {
            $this->error('The OPT Recruiter track does not exist. Run the recruiter migrations first.');

            return self::FAILURE;
        }

        $rows = [];
        $failed = false;

        foreach (RecruiterTrainingContent::optTrack() as $plan) {
            $course = TrainingCourse::query()->with('track:id,name')->where('slug', Str::slug($plan['course']))->first();

            if ($course === null) {
                $rows[] = [$plan['course'], '-', 'missing: course not found'];
                $failed = true;

                continue;
            }

            if ($course->training_track_id === $track->id) {
                $rows[] = [$course->title, $course->id, 'already in OPT Recruiter'];

                continue;
            }

            if ($course->training_track_id !== null) {
                $rows[] = [$course->title, $course->id, 'skipped: in '.($course->track->name ?? 'another track')];
                $failed = true;

                continue;
            }

            if (! $dryRun) {
                $course->timestamps = false;
                $course->training_track_id = $track->id;
                $course->save();
            }

            $rows[] = [$course->title, $course->id, $dryRun ? 'would move to OPT Recruiter' : 'moved to OPT Recruiter'];
        }

        $this->table(['Course', 'ID', 'Result'], $rows);
        $this->line(($dryRun ? 'Dry run, nothing saved. ' : '').'Courses in OPT Recruiter: '.TrainingCourse::query()->where('training_track_id', $track->id)->count().'. Not in a track: '.TrainingCourse::query()->whereNull('training_track_id')->count().'.');

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
