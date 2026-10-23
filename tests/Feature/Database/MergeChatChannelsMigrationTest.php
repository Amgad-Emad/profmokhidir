<?php

namespace Tests\Feature\Database;

use App\Constants\ChatSettings;
use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\ChatRead;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MergeChatChannelsMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function migration(): Migration
    {
        return require database_path('migrations/2026_10_23_062533_merge_chat_channels_into_one_room.php');
    }

    #[Test]
    public function every_channel_is_merged_into_the_oldest_with_ids_and_order_preserved(): void
    {
        $general = ChatChannel::factory()->general()->create();
        $soil = ChatChannel::factory()->create(['slug' => 'soil-science']);
        $amina = User::factory()->member()->create();
        $yusuf = User::factory()->member()->create();

        $ids = [];
        foreach ([$general, $soil, $general, $soil, $general] as $i => $channel) {
            $ids[] = ChatMessage::factory()->for($channel, 'channel')->for($i % 2 ? $yusuf : $amina, 'user')->create(['body' => "m{$i}"])->id;
        }
        ChatRead::query()->create(['channel_id' => $general->id, 'user_id' => $amina->id, 'last_read_message_id' => $ids[2]]);
        ChatRead::query()->create(['channel_id' => $soil->id, 'user_id' => $amina->id, 'last_read_message_id' => $ids[3]]);
        ChatRead::query()->create(['channel_id' => $soil->id, 'user_id' => $yusuf->id, 'last_read_message_id' => $ids[1]]);

        $this->migration()->up();

        $room = ChatChannel::query()->sole();
        $this->assertSame($general->id, $room->id);
        $this->assertSame([ChatSettings::ROOM_NAME, ChatSettings::ROOM_SLUG], [$room->name, $room->slug]);
        $this->assertSame($ids, ChatMessage::query()->where('channel_id', $room->id)->orderBy('id')->pluck('id')->all());
        $this->assertSame(['m0', 'm1', 'm2', 'm3', 'm4'], ChatMessage::query()->orderBy('id')->pluck('body')->all());
        $this->assertSame(max($ids), $room->last_message_id);

        // Amina's room cursor wins; Yusuf, who only read elsewhere, keeps his.
        $this->assertSame($ids[2], ChatRead::query()->where('user_id', $amina->id)->sole()->last_read_message_id);
        $this->assertSame($ids[1], ChatRead::query()->where('user_id', $yusuf->id)->sole()->last_read_message_id);
    }

    #[Test]
    public function an_empty_database_is_left_alone_and_running_twice_changes_nothing(): void
    {
        $this->migration()->up();
        $this->assertSame(0, ChatChannel::query()->count());

        ChatChannel::factory()->room()->create();
        $this->migration()->up();
        $this->migration()->up();
        $this->assertSame([ChatSettings::ROOM_SLUG], ChatChannel::query()->pluck('slug')->all());
    }
}
