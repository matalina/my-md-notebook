<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Mockery;
use Laravel\Socialite\Facades\Socialite;

class SocialLoginsTest extends TestCase
{
    /**
     * @test
     */
    public function facebook_login_redirects_successfully()
    {
        $response = $this->get('/api/login/facebook');

        $response->assertRedirect();
        $response->assertSee('https://www.facebook.com/v3.3/dialog/oauth',true);
    }

    /**
     * @test
     */
    public function google_login_redirects_successfully()
    {
        $response = $this->get('/api/login/google');

        $response->assertRedirect();
        $response->assertSee('https://accounts.google.com/o/oauth2/auth',true);
    }

    /**
     * @test
     */
    public function wrong_provider_login_returns_error()
    {
        $response = $this->get('/api/login/something');

        $response->assertStatus(422);
        $content = json_decode($response->getContent(), true);
        $response->assertJson($content);
        $this->assertEquals('Please login using facebook or google', $content['error']);
    }

    /**
     * @test
     */
    public function wrong_provider_login_callback_returns_error()
    {
        $response = $this->get('/api/login/something/callback');

        $response->assertStatus(422);
        $content = json_decode($response->getContent(), true);
        $response->assertJson($content);
        $this->assertEquals('Please login using facebook or google', $content['error']);
    }

    /**
     * @test
     */
    public function facebook_login_callback_creates_new_user()
    {
        $abstractUser = Mockery::mock('Laravel\Socialite\Two\User');

        $abstractUser
            ->shouldReceive('getId')
            ->andReturn(rand())
            ->shouldReceive('getName')
            ->andReturn(\Str::random(10))
            ->shouldReceive('getEmail')
            ->andReturn(\Str::random(10) . '@gmail.com')
            ->shouldReceive('getAvatar')
            ->andReturn('https://en.gravatar.com/userimage');

        Socialite::shouldReceive('driver->user')->andReturn($abstractUser);


        $response = $this->get('/api/login/facebook/callback');
        $response->assertStatus(200);
        $response->assertHeader('Access-Token');
        $this->assertDatabaseHas('users',['email' => $abstractUser->getEmail()]);
    }

    /**
     * @test
     */
    public function google_login_callback_creates_new_user()
    {
        $abstractUser = Mockery::mock('Laravel\Socialite\Two\User');

        $abstractUser
            ->shouldReceive('getId')
            ->andReturn(rand())
            ->shouldReceive('getName')
            ->andReturn(\Str::random(10))
            ->shouldReceive('getEmail')
            ->andReturn(\Str::random(10) . '@gmail.com')
            ->shouldReceive('getAvatar')
            ->andReturn('https://en.gravatar.com/userimage');

        Socialite::shouldReceive('driver->user')->andReturn($abstractUser);


        $response = $this->get('/api/login/google/callback');
        $response->assertStatus(200);
        $response->assertHeader('Access-Token');
        $this->assertDatabaseHas('users',['email' => $abstractUser->getEmail()]);
    }

     /**
     * @test
     */
    public function google_and_facebook_with_same_email_have_only_one_account()
    {
        $abstractUser1 = Mockery::mock('Laravel\Socialite\Two\User');
        $abstractUser2 = Mockery::mock('Laravel\Socialite\Two\User');

        $abstractUser1
            ->shouldReceive('getId')
            ->andReturn(rand())
            ->shouldReceive('getName')
            ->andReturn(\Str::random(10))
            ->shouldReceive('getEmail')
            ->andReturn(\Str::random(10) . '@gmail.com')
            ->shouldReceive('getAvatar')
            ->andReturn('https://en.gravatar.com/userimage');

        Socialite::shouldReceive('driver->user')->andReturn($abstractUser1);
        $response = $this->get('/api/login/google/callback');

        $this->assertDatabaseHas('users',['email' => $abstractUser1->getEmail()]);
        $count = User::where('email','=',$abstractUser1->getEmail())->count();
        $this->assertTrue($count == 1);

        $abstractUser2
            ->shouldReceive('getId')
            ->andReturn(rand())
            ->shouldReceive('getName')
            ->andReturn(\Str::random(10))
            ->shouldReceive('getEmail')
            ->andReturn($abstractUser1->getEmail())
            ->shouldReceive('getAvatar')
            ->andReturn('https://en.gravatar.com/userimage');

        Socialite::shouldReceive('driver->user')->andReturn($abstractUser2);
        $response = $this->get('/api/login/facebook/callback');

        $this->assertDatabaseHas('users',['email' => $abstractUser1->getEmail()]);
        $count = User::where('email','=',$abstractUser1->getEmail())->count();
        $this->assertTrue($count == 1);

        $count = User::where('email','=',$abstractUser2->getEmail())->count();
        $this->assertTrue($count == 1);
    }


}
