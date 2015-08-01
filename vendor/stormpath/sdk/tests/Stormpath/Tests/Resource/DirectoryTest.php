<?php
/*
 * Copyright 2013 Stormpath, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Stormpath\Tests\Resource;


class DirectoryTest extends \Stormpath\Tests\BaseTest {

    private static $directory;
    private static $inited;

    protected static function init()
    {
        self::$directory = \Stormpath\Resource\Directory::instantiate(array('name' => 'Main Directory' .md5(time()), 'description' => 'Main Directory description'));
        self::createResource(\Stormpath\Resource\Directory::PATH, self::$directory);
        self::$inited = true;
    }

    public function setUp()
    {
        if (!self::$inited)
        {
            self::init();
        }
    }

    public static function tearDownAfterClass()
    {
        if (self::$directory)
        {
            self::$directory->delete();
        }
    }

    public function testGet()
    {
        $directory = \Stormpath\Resource\Directory::get(self::$directory->href);

        $this->assertInstanceOf('\Stormpath\Resource\Directory', $directory);
        $this->assertContains('Main Directory', $directory->name);
        $this->assertContains('Main Directory description', $directory->description);
        $this->assertInstanceOf('\Stormpath\Resource\GroupList', $directory->groups);
        $this->assertInstanceOf('\Stormpath\Resource\AccountList', $directory->accounts);
        $this->assertInstanceOf('\Stormpath\Resource\Tenant', $directory->tenant);
        $this->assertEquals(self::$client->tenant->name, $directory->tenant->name);
    }

    public function testCreate()
    {
        $directory = \Stormpath\Resource\Directory::create(array('name' => 'A random directory' .md5(time()), 'description' => 'A Random Directory description', 'status' => 'disabled'));

        $this->assertInstanceOf('\Stormpath\Resource\Directory', $directory);
        $this->assertContains('A random directory', $directory->name);
        $this->assertEquals('A Random Directory description', $directory->description);
        $this->assertEquals('DISABLED', $directory->status);

        $directory->delete();
    }

    public function testSave()
    {
        $directory = self::$directory;

        $directory->name = 'Main Directory Changed' .md5(time());
        $directory->status = 'disabled';
        $directory->description = 'Main Directory description changed';
        $directory->save();

        $directory = \Stormpath\Resource\Directory::get(self::$directory->href);
        $this->assertContains('Main Directory Changed', $directory->name);
        $this->assertContains('Main Directory description changed', $directory->description);
        $this->assertEquals('DISABLED', $directory->status);
    }

    public function testCreateAccount()
    {
        $directory = self::$directory;
        $directory->status = 'enabled';
        $directory->save();

        $account = \Stormpath\Resource\Account::instantiate(array('givenName' => 'Account Name',
                                                                   'surname' => 'Surname',
                                                                   'email' => md5(time()) .'@unknown123.kot',
                                                                   'password' => 'superP4ss'));

        $directory->createAccount($account, array('registrationWorkflowEnabled' => false));

        $account = \Stormpath\Resource\Account::get($account->href);

        $this->assertContains('Main Directory', $account->directory->name);
        $this->assertEquals('Account Name', $account->givenName);

        $account->delete();
    }

    public function testCreateGroup()
    {
        $directory = self::$directory;
        $directory->status = 'enabled';
        $directory->save();

        $group = \Stormpath\Resource\Group::instantiate(array('name' => 'New Group' . md5(time())));

        $directory->createGroup($group);

        $group = \Stormpath\Resource\Group::get($group->href);

        $this->assertContains('Main Directory', $group->directory->name);
        $this->assertContains('New Group', $group->name);

        $group->delete();
    }

    public function testAddingCustomData()
    {
        $cd = self::$directory->customData;

        $cd->unitTest = "unit Test";
        $cd->save();

        $directory = \Stormpath\Resource\Directory::get(self::$directory->href);
        $customData = $directory->customData;
        $this->assertEquals('unit Test', $customData->unitTest);



    }

    public function testUpdatingCustomData()
    {
        $cd = self::$directory->customData;

        $cd->unitTest = "some change";
        $cd->save();

        $directory = \Stormpath\Resource\Directory::get(self::$directory->href);
        $customData = $directory->customData;
        $this->assertEquals('some change', $customData->unitTest);

    }

    public function testRemovingCustomData()
    {
        $cd = self::$directory->customData;

        $cd->remove('unitTest');

        $directory = \Stormpath\Resource\Directory::get(self::$directory->href);
        $customData = $directory->customData;
        $this->assertNull($customData->unitTest);
    }

    public function testDeletingAllCustomData()
    {
        $cd = self::$directory->customData;
        $cd->unitTest = "some change";
        $cd->rank = "Captain";
        $cd->birthDate = "2305-07-13";
        $cd->favoriteDrink = "favoriteDrink";
        $cd->save();

        $cd->delete();

        $directory = \Stormpath\Resource\Directory::get(self::$directory->href);
        $customData = $directory->customData;
        $this->assertNull($customData->unitTest);
        $this->assertNull($customData->rank);
        $this->assertNull($customData->birthDate);
        $this->assertNull($customData->favoriteDrink);
    }

    /**
     * @expectedException \Stormpath\Resource\ResourceError
     */
    public function testDelete()
    {
        $directory = \Stormpath\Resource\Directory::create(array('name' => 'Another random directory' .md5(time())));

        $this->assertInstanceOf('\Stormpath\Resource\Directory', $directory);
        $this->assertContains('Another random directory', $directory->name);

        $href = $directory->href;

        $directory->delete();

        \Stormpath\Resource\Directory::get($href);
    }

}