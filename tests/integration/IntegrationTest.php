<?php

namespace GetStream\Integration;

use GetStream\StreamChat\Client;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class IntegrationTest extends TestCase
{
    /**
     * @var Client
     */
    protected $client;

    protected function setUp():void
    {
        $this->client = new Client(
            getenv('STREAM_API_KEY'),
            getenv('STREAM_API_SECRET'),
            'v1.0',
            getenv('STREAM_REGION')
        );
        $this->client->setLocation('qa');
        $this->client->timeout = 10000;
    }

    public function testAuth()
    {
        $this->expectException(\GetStream\StreamChat\StreamException::class);
        $this->client = new Client("bad", "guy");
        $this->client->getAppSettings();
    }

    public function testChannelTypes()
    {
        $response = $this->client->getChannelType("team");
        $this->assertTrue(array_key_exists("permissions", $response));
    }

    public function testListChannelTypes()
    {
        $response = $this->client->listChannelTypes();
        $this->assertTrue(array_key_exists("channel_types", $response));
    }

    private function getUser(){
        // this creates a user on the server
        $user = ["id" => Uuid::uuid4()->toString()];
        $response = $this->client->updateUser($user);
        $this->assertTrue(array_key_exists("users", $response));
        $this->assertTrue(array_key_exists($user["id"], $response["users"]));
        return $user;
    }

    public function testMuteUser()
    {
        $user1 = $this->getUser();
        $user2 = $this->getUser();
        $response = $this->client->muteUser($user1["id"], $user2["id"]);
        $this->assertTrue(array_key_exists("mute", $response));
        $this->assertSame($response["mute"]["target"]["id"], $user1["id"]);
    }

    public function testGetAppSettings()
    {
        $response = $this->client->getAppSettings();
        $this->assertTrue(array_key_exists("app", $response));
    }

    public function testUpdateUser()
    {
        $user = ["id" => Uuid::uuid4()->toString()];
        $response = $this->client->updateUser($user);
        $this->assertTrue(array_key_exists("users", $response));
        $this->assertTrue(array_key_exists($user["id"], $response["users"]));
    }

    public function testUpdateUsers()
    {
        $user = ["id" => Uuid::uuid4()->toString()];
        $response = $this->client->updateUsers([$user]);
        $this->assertTrue(array_key_exists("users", $response));
        $this->assertTrue(array_key_exists($user["id"], $response["users"]));
    }

    public function testDeleteUser()
    {
        $user = $this->getUser();
        $response = $this->client->deleteUser($user["id"]);
        $this->assertTrue(array_key_exists("user", $response));
        $this->assertSame($user["id"], $response["user"]["id"]);
    }

    public function testDeactivateUser()
    {
        $user = $this->getUser();
        $response = $this->client->deactivateUser($user["id"]);
        $this->assertTrue(array_key_exists("user", $response));
        $this->assertSame($user["id"], $response["user"]["id"]);
    }

    public function createFellowship()
    {
        $members = [
            ["id" => "frodo-baggins", "name" => "Frodo Baggins", "race" => "Hobbit", "age" => 50],
            ["id" => "sam-gamgee", "name" => "Samwise Gamgee", "race" => "Hobbit", "age" => 38],
            ["id" => "gandalf", "name" => "Gandalf the Grey", "race" => "Istari"],
            ["id" => "legolas", "name" => "Legolas", "race" => "Elf", "age" => 500],
            ["id" => "gimli", "name" => "Gimli", "race" => "Dwarf", "age" => 139],
            ["id" => "aragorn", "name" => "Aragorn", "race" => "Man", "age" => 87],
            ["id" => "boromir", "name" => "Boromir", "race" => "Man", "age" => 40],
            [
                "id" => "meriadoc-brandybuck",
                "name" => "Meriadoc Brandybuck",
                "race" => "Hobbit",
                "age" => 36,
            ],
            ["id" => "peregrin-took", "name" => "Peregrin Took", "race" => "Hobbit", "age" => 28],
        ];
        $this->client->updateUsers($members);
        $user_ids = [];
        foreach($members as $user){
            $user_ids[] = $user['id'];
        }
        $channel = $this->client->getChannel(
            "team", "fellowship-of-the-ring", ["members" => $user_ids]
        );

        $channel->create("gandalf");
    }

    public function testExportUser()
    {
        $this->createFellowship();
        $response = $this->client->exportUser("gandalf");
        $this->assertTrue(array_key_exists("user", $response));
        $this->assertSame("Gandalf the Grey", $response["user"]["name"]);
    }

    public function testBanUser()
    {
        $user1 = $this->getUser();
        $user2 = $this->getUser();
        $response = $this->client->banUser($user1["id"], ["user_id" => $user2["id"]]);
    }

    public function testUnBanUser()
    {
        $user1 = $this->getUser();
        $user2 = $this->getUser();
        $response = $this->client->banUser($user1["id"], ["user_id" => $user2["id"]]);
        $response = $this->client->unBanUser($user1["id"], ["user_id" => $user2["id"]]);
    }

    public function testFlagUser()
    {
        $user1 = $this->getUser();
        $user2 = $this->getUser();
        $response = $this->client->flagUser($user1["id"], ["user_id" => $user2["id"]]);
    }

    public function testUnFlagUser()
    {
        $user1 = $this->getUser();
        $user2 = $this->getUser();
        $response = $this->client->flagUser($user1["id"], ["user_id" => $user2["id"]]);
        $response = $this->client->unFlagUser($user1["id"], ["user_id" => $user2["id"]]);
    }

    public function testMarkAllRead()
    {
        $user1 = $this->getUser();
        $response = $this->client->markAllRead($user1["id"]);
    }

    public function getChannel()
    {
         $channel = $this->client->getChannel(
             "messaging",
             Uuid::uuid4()->toString(),
             ["test" => true, "language" => "php"]
         );
         $channel->create($this->getUser()["id"]);
         return $channel;
    }

    public function testUpdateMessage()
    {
        $user = $this->getUser();
        $channel = $this->getChannel();
        $msgId = Uuid::uuid4()->toString();
        $msg = ["id" => $msgId, "text" => "hello world"];
        $response = $channel->sendMessage($msg, $user["id"]);
        $this->assertSame("hello world", $response["message"]["text"]);
        $msg = [
            "id" => $msgId,
            "text" => "hello world",
            "awesome" => true,
            "user" => ["id" => $response["message"]["user"]["id"]]
        ];
        $response = $this->client->updateMessage($msg);
    }

    public function testDeleteMessage()
    {
        $user = $this->getUser();
        $channel = $this->getChannel();
        $msgId = Uuid::uuid4()->toString();
        $msg = ["id" => $msgId, "text" => "helloworld"];
        $response = $channel->sendMessage($msg, $user["id"]);
        $response = $this->client->deleteMessage($msgId);
    }

    public function testFlagMessage()
    {
        $user = $this->getUser();
        $user2 = $this->getUser();
        $channel = $this->getChannel();
        $msgId = Uuid::uuid4()->toString();
        $msg = ["id" => $msgId, "text" => "hello world"];
        $response = $channel->sendMessage($msg, $user["id"]);
        $response = $this->client->flagMessage($msgId, ["user_id" => $user2["id"]]);
    }

    public function testUnFlagMessage()
    {
        $user = $this->getUser();
        $user2 = $this->getUser();
        $channel = $this->getChannel();
        $msgId = Uuid::uuid4()->toString();
        $msg = ["id" => $msgId, "text" => "hello world"];
        $response = $channel->sendMessage($msg, $user["id"]);
        $response = $this->client->flagMessage($msgId, ["user_id" => $user2["id"]]);
        $response = $this->client->unFlagMessage($msgId, ["user_id" => $user2["id"]]);
    }

    public function testQueryUsersYoungHobbits()
    {
        $this->createFellowship();
        $response = $this->client->queryUsers(
            ["race" => ['$eq' => "Hobbit"]],
            ["age" => -1]
        );
        $this->assertSame(count($response["users"]), 4);
        $ages = [];
        foreach($response["users"] as $user){
            $ages[] = $user["age"];
        }
        $this->assertEquals([50, 38, 36, 28], $ages);
    }

    public function testQueryChannelsMembersIn()
    {
        $this->createFellowship();
        $response = $this->client->queryChannels(
            ["members" => ['$in' => ["gimli"]]],
            ["id" => 1]
        );
        $this->assertSame(count($response["channels"]), 1);
        $this->assertSame(count($response["channels"][0]["members"]), 9);
    }

    public function testDevices()
    {
        $user = $this->getUser();
        $response = $this->client->getDevices($user["id"]);
        $this->assertTrue(array_key_exists("devices", $response));
        $this->assertSame(count($response["devices"]), 0);
        $this->client->addDevice(Uuid::uuid4()->toString(), "apn", $user["id"]);
        $response = $this->client->getDevices($user["id"]);
        $this->assertSame(count($response["devices"]), 1);
        $response = $this->client->deleteDevice($response["devices"][0]["id"], $user["id"]);
        $response = $this->client->getDevices($user["id"]);
        $this->assertSame(count($response["devices"]), 0);
        // overdoing it a little?
        $this->client->addDevice(Uuid::uuid4()->toString(), "apn", $user["id"]);
        $response = $this->client->getDevices($user["id"]);
        $this->assertSame(count($response["devices"]), 1);
    }

    public function testChannelBanUser()
    {
        $user = $this->getUser();
        $user2 = $this->getUser();
        $channel = $this->getChannel();
        $channel->banUser($user["id"], ["user_id" => $user2["id"]]);
        $channel->banUser($user["id"], [
            "user_id" => $user2["id"],
            "timeout" => 3600,
            "reason" => "offensive language is not allowed here"
        ]);
        $channel->unBanUser($user["id"]);
    }

    public function testChannelCreateWithoutId()
    {
        $user = $this->getUser();
        $user2 = $this->getUser();
        $user_ids = [$user["id"], $user2["id"]];
        $channel = $this->client->getChannel(
            "messaging",
            null,
            ["members" => $user_ids]
        );
        $this->assertNull($channel->id);
        $channel->create($this->getUser()["id"]);
        $this->assertNotNull($channel->id);
    }

    public function testChannelSendEvent()
    {
        $user = $this->getUser();
        $channel = $this->getChannel();
        $response = $channel->sendEvent(["type" => "typing.start"], $user["id"]);
        $this->assertTrue(array_key_exists("event", $response));
        $this->assertSame($response["event"]["type"], "typing.start");
    }

    public function testChannelSendReaction()
    {
        $user = $this->getUser();
        $channel = $this->getChannel();

        $msg = $channel->sendMessage(["text" => "hello world"], $user["id"]);
        $response = $channel->sendReaction(
            $msg["message"]["id"],
            ["type" => "love"],
            $user["id"]);
        $this->assertTrue(array_key_exists("message", $response));
        $this->assertSame($response["message"]["latest_reactions"][0]["type"], "love");
        $this->assertSame(count($response["message"]["latest_reactions"]), 1);
    }

    public function testChannelDeleteReaction()
    {
        $user = $this->getUser();
        $channel = $this->getChannel();

        $msg = $channel->sendMessage(["text" => "hi"], $user["id"]);
        $response = $channel->sendReaction(
            $msg["message"]["id"],
            ["type" => "love"],
            $user["id"]);
        $response = $channel->deleteReaction(
            $msg["message"]["id"],
            "love",
            $user["id"]);
        $this->assertTrue(array_key_exists("message", $response));
        $this->assertSame(count($response["message"]["latest_reactions"]), 0);
    }

    public function testChannelUpdate()
    {
        $channel = $this->getChannel();
        $response = $channel->update(["motd" => "one apple a day"]);
        $this->assertTrue(array_key_exists("channel", $response));
        $this->assertSame($response["channel"]["motd"], "one apple a day");
    }

    public function testChannelDelete()
    {
        $channel = $this->getChannel();
        $response = $channel->delete();
        $this->assertTrue(array_key_exists("channel", $response));
        $this->assertNotNull($response["channel"]["deleted_at"]);
    }

    public function testChannelTruncate()
    {
        $channel = $this->getChannel();
        $response = $channel->truncate();
        $this->assertTrue(array_key_exists("channel", $response));
    }

    public function testChannelAddMembers()
    {
        $user = $this->getUser();
        $channel = $this->getChannel();
        $response = $channel->removeMembers([$user["id"]]);
        $this->assertTrue(array_key_exists("members", $response));
        $this->assertSame(count($response["members"]), 0);
        $response = $channel->addMembers([$user["id"]]);
        $this->assertTrue(array_key_exists("members", $response));
        $this->assertSame(count($response["members"]), 1);
        if(array_key_exists("is_moderator", $response["members"][0])){
            $this->assertFalse($response["members"][0]["is_moderator"]);
        }
    }

    public function testChannelAddModerators()
    {
        $user = $this->getUser();
        $channel = $this->getChannel();
        $response = $channel->addModerators([$user["id"]]);
        $this->assertTrue($response["members"][0]["is_moderator"]);

        $response = $channel->demoteModerators([$user["id"]]);
        if(array_key_exists("is_moderator", $response["members"][0])){
            $this->assertFalse($response["members"][0]["is_moderator"]);
        }
    }

    public function testChannelMarkRead()
    {
        $user = $this->getUser();
        $channel = $this->getChannel();
        $response = $channel->markRead($user["id"]);
        $this->assertTrue(array_key_exists("event", $response));
        $this->assertSame($response["event"]["type"], "message.read");
    }

    public function testChannelGetReplies()
    {
        $user = $this->getUser();
        $channel = $this->getChannel();

        $msg = $channel->sendMessage(["text" => "hi"], $user["id"]);
        $response = $channel->getReplies($msg["message"]["id"]);
        $this->assertTrue(array_key_exists("messages", $response));
        $this->assertSame(count($response["messages"]), 0);
        for($i=0;$i<10;$i++){
            $rpl = $channel->sendMessage(
                [
                    "text" => "hi",
                    "index" => $i,
                    "parent_id" => $msg["message"]["id"]
                ], $user["id"]);
        }
        $response = $channel->getReplies($msg["message"]["id"]);
        $this->assertSame(count($response["messages"]), 10);

        $response = $channel->getReplies(
            $msg["message"]["id"], [
                "limit" => 3,
                "offset" => 3]);
        $this->assertSame(count($response["messages"]), 3);
        $this->assertSame($response["messages"][0]["index"], 7);
    }

    public function testChannelGetReactions()
    {
        $user = $this->getUser();
        $channel = $this->getChannel();

        $msg = $channel->sendMessage(["text" => "hi"], $user["id"]);
        $response = $channel->getReactions($msg["message"]["id"]);
        $this->assertTrue(array_key_exists("reactions", $response));
        $this->assertSame(count($response["reactions"]), 0);

        $channel->sendReaction(
            $msg["message"]["id"],
            [
                "type" => "love",
                "count" => 42
            ], $user["id"]);

        $channel->sendReaction(
            $msg["message"]["id"],
            [
                "type" => "clap",
            ], $user["id"]);

        $response = $channel->getReactions($msg["message"]["id"]);
        $this->assertSame(count($response["reactions"]), 2);

        $response = $channel->getReactions(
            $msg["message"]["id"], [
                "offset" => 1]);
        $this->assertSame(count($response["reactions"]), 1);
        $this->assertSame($response["reactions"][0]["count"], 42);
    }

}
