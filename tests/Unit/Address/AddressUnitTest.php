<?php

namespace Tests\Unit\Address;

use App\Addresses\Address;
use App\Addresses\Exceptions\AddressInvalidArgumentException;
use App\Addresses\Exceptions\AddressNotFoundException;
use App\Addresses\Repositories\AddressRepository;
use App\Addresses\Transformations\AddressTransformable;
use App\Customers\Customer;
use App\Customers\Repositories\CustomerRepository;
use App\Provinces\Province;
use App\Cities\City;
use App\Countries\Country;
use Tests\TestCase;

class AddressUnitTest extends TestCase
{
    use AddressTransformable;
    
    /** @test */
    public function it_errors_when_the_address_is_not_found()
    {
        $this->expectException(AddressNotFoundException::class);

        $address = new AddressRepository(new Address);
        $address->findAddressById(999);
    }
    
    /** @test */
    public function it_can_list_all_the_addresses()
    {
        $address = factory(Address::class)->create();

        $address = new AddressRepository($address);
        $addresses = $address->listAddress();

        foreach ($addresses as $list) {
            $this->assertDatabaseHas('addresses', ['alias' => $list->alias]);
        }
    }

    /** @test */
    public function it_errors_when_creating_an_address()
    {
        $this->expectException(AddressInvalidArgumentException::class);

        $address = new AddressRepository(new Address);
        $address->createAddress(['alias' => null]);
    }
    
    /** @test */
    public function it_can_show_the_address()
    {
        $address = factory(Address::class)->create();

        $this->assertDatabaseHas('addresses', ['id' => $address->id]);
    }
    
    /** @test */
    public function it_can_list_all_the_addresses_of_the_customer()
    {
        $customer = factory(Customer::class)->create();
        factory(Address::class)->create(['customer_id' => $customer->id]);

        $customerRepo = new CustomerRepository($customer);
        $lists = $customerRepo->findAddresses();

        foreach ($lists as $list) {
            $this->assertEquals($customer->id, $list->customer_id);
        }
    }
    
    /** @test */
    public function it_can_soft_delete_the_address()
    {
        $created = factory(Address::class)->create();

        $address = new AddressRepository($created);
        $address->deleteAddress();

        $this->assertDatabaseHas('addresses', ['id' => $created->id]);
    }

    /** @test */
    public function it_can_update_the_address()
    {
        $address = factory(Address::class)->create();

        $update = [
            'alias' => $this->faker->unique()->word,
            'address_1' => $this->faker->unique()->word,
            'address_2' => null,
            'zip' => 1101,
            'status' => 1
        ];

        $addressRepo = new AddressRepository($address);
        $updated = $addressRepo->updateAddress($update);

        $this->assertTrue($updated);
        $this->assertEquals($update['alias'], $address->alias);
        $this->assertEquals($update['address_1'], $address->address_1);
        $this->assertEquals($update['address_2'], $address->address_2);
        $this->assertEquals($update['zip'], $address->zip);
        $this->assertEquals($update['status'], $address->status);
    }
    
    /** @test */
    public function it_can_create_the_address()
    {
        $country = factory(Country::class)->create();
        $province = factory(Province::class)->create();
        $city = factory(City::class)->create();
        $customer = factory(Customer::class)->create();

        $params = [
            'alias' => $this->faker->word,
            'address_1' => $this->faker->streetName,
            'address_2' => $this->faker->streetAddress,
            'zip' => $this->faker->postcode,
            'city_id' => $city->id,
            'province_id' => $province->id,
            'country_id' => $country->id,
            'customer_id' => $customer->id,
            'status' => 1
        ];

        $addressRepo = new AddressRepository(new Address);
        $address = $addressRepo->createAddress($params);

        $created = $this->transformAddress($address);

        $this->assertInstanceOf(Address::class, $created);
        $this->assertEquals($params['alias'], $created->alias);
        $this->assertEquals($params['address_1'], $created->address_1);
        $this->assertEquals($params['address_2'], $created->address_2);
        $this->assertEquals($params['zip'], $created->zip);
        $this->assertEquals($params['status'], $created->status);
        $this->assertEquals($customer->id, $created->customer_id);
    }
}