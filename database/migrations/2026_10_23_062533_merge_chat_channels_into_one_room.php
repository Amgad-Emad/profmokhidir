<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * One room instead of channels. The oldest channel becomes the Scientific
 * Community room; every message of the other channels moves into it with its
 * id unchanged (ids are the only ordering key, so the merged history reads in
 * the order it was written), read cursors are merged and the other channels
 * are removed. chat_channels and channel_id stay, so the reliability code is
 * untouched. On an empty database nothing happens; the room is created on
 * first use or by the seeder.
 */
return new class extends Migration
{
    private const string SLUG = 'scientific-community';

    private const string NAME = 'Scientific Community';

    private const string DESCRIPTION = 'One conversation for the approved members of the Scientific Community.';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $room = DB::table('chat_channels')->where('slug', self::SLUG)->first()
            ?? DB::table('chat_channels')->orderBy('id')->first();

        if ($room === null) {
            return;
        }

        $roomId = (int) $room->id;

        DB::transaction(function () use ($roomId): void {
            $others = DB::table('chat_channels')->where('id', '!=', $roomId)->pluck('id')->all();

            if ($others !== []) {
                // Messages keep their ids; only the channel changes.
                DB::table('chat_messages')->whereIn('channel_id', $others)->update(['channel_id' => $roomId]);

                // A member's cursor in the room wins; a member who only read elsewhere keeps
                // the lowest of their other cursors (never marks unseen messages as read).
                $kept = DB::table('chat_reads')->where('channel_id', $roomId)->pluck('user_id')->all();
                $moved = DB::table('chat_reads')
                    ->whereIn('channel_id', $others)
                    ->whereNotIn('user_id', $kept)
                    ->selectRaw('user_id, MIN(last_read_message_id) as last_read_message_id, MAX(updated_at) as updated_at')
                    ->groupBy('user_id')
                    ->get();

                DB::table('chat_reads')->whereIn('channel_id', $others)->delete();

                foreach ($moved as $read) {
                    DB::table('chat_reads')->insert([
                        'channel_id' => $roomId,
                        'user_id' => $read->user_id,
                        'last_read_message_id' => $read->last_read_message_id,
                        'created_at' => $read->updated_at,
                        'updated_at' => $read->updated_at,
                    ]);
                }

                DB::table('chat_channels')->whereIn('id', $others)->delete();
            }

            DB::table('chat_channels')->where('id', $roomId)->update([
                'name' => self::NAME,
                'slug' => self::SLUG,
                'description' => self::DESCRIPTION,
                'last_message_id' => DB::table('chat_messages')->where('channel_id', $roomId)->whereNull('deleted_at')->max('id'),
                'updated_at' => now(),
            ]);
        });
    }

    /**
     * Reverse the migrations. The merge cannot be undone (the original channel
     * of each message is not kept); rolling back leaves the single room.
     */
    public function down(): void
    {
        //
    }
};
