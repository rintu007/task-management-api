<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_sql_injection_prevention(): void
    {
        $user = $this->actingAsUser();

        $response = $this->postJson('/api/tasks', [
            'title' => "Test'; DROP TABLE users;--",
            'description' => 'Test description for SQL injection test that is long enough',
            'status' => 'pending',
        ]);

        $response->assertStatus(201);
        
        // Verify the input was stored safely (Eloquent protects against SQL injection)
        $this->assertDatabaseHas('tasks', [
            'title' => "Test'; DROP TABLE users;--",
        ]);
        
        // Verify users table still exists
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_xss_prevention(): void
    {
        $user = $this->actingAsUser();

        $response = $this->postJson('/api/tasks', [
            'title' => 'Normal Title <script>alert("xss")</script>',
            'description' => 'Normal description content that is long enough to pass validation after sanitization',
            'status' => 'pending',
        ]);

        $response->assertStatus(201);
        
        // Check that script tags and their content are completely removed
        $task = \App\Models\Task::first();
        
        $this->assertStringNotContainsString('<script>', $task->title);
        $this->assertStringNotContainsString('</script>', $task->title);
        $this->assertStringNotContainsString('alert("xss")', $task->title);
        
        $this->assertStringNotContainsString('<script>', $task->description);
        $this->assertStringNotContainsString('</script>', $task->description);
        $this->assertStringNotContainsString('alert("xss")', $task->description);
        
        // The title should be just "Normal Title" (script content completely removed)
        $this->assertEquals('Normal Title', $task->title);
    }

    public function test_rate_limiting_on_login(): void
    {
        // Create a test user first
        $user = \App\Models\User::factory()->create([
            'email' => 'rate@example.com',
            'password' => bcrypt('password'),
        ]);

        $throttled = false;
        
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/login', [
                'email' => 'rate@example.com',
                'password' => 'wrongpassword', // Wrong password to trigger failures
            ]);

            if ($response->getStatusCode() === 429) {
                $throttled = true;
                break;
            }
        }

        $this->assertTrue($throttled, 'Rate limiting should block after multiple attempts');
    }

    public function test_html_tags_are_stripped(): void
    {
        $user = $this->actingAsUser();

        $response = $this->postJson('/api/tasks', [
            'title' => 'Test <strong>Bold</strong> and <em>Italic</em>',
            'description' => 'Description with <div>div</div> and <span>span</span> tags',
            'status' => 'pending',
        ]);

        $response->assertStatus(201);
        
        $task = \App\Models\Task::first();
        
        // All HTML tags should be stripped
        $this->assertStringNotContainsString('<strong>', $task->title);
        $this->assertStringNotContainsString('</strong>', $task->title);
        $this->assertStringNotContainsString('<em>', $task->title);
        $this->assertStringNotContainsString('</em>', $task->title);
        $this->assertStringNotContainsString('<div>', $task->description);
        $this->assertStringNotContainsString('</div>', $task->description);
        $this->assertStringNotContainsString('<span>', $task->description);
        $this->assertStringNotContainsString('</span>', $task->description);
        
        // Should only contain plain text (tags removed but content preserved)
        $this->assertEquals('Test Bold and Italic', $task->title);
        $this->assertEquals('Description with div and span tags', $task->description);
    }

    public function test_minimum_length_validation_still_works_after_sanitization(): void
    {
        $user = $this->actingAsUser();

        // This input becomes too short after sanitization
        $response = $this->postJson('/api/tasks', [
            'title' => 'A <script>very long script content that when removed leaves only A</script>', 
            'description' => 'Short <script>and this becomes too short</script>',
            'status' => 'pending',
        ]);

        // Should fail validation due to min length requirements after sanitization
        // The title becomes just "A" and description becomes "Short"
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'description']);
    }

    public function test_complete_script_removal(): void
    {
        $user = $this->actingAsUser();

        $response = $this->postJson('/api/tasks', [
            'title' => 'Before <script>alert("This entire content should be removed")</script> After',
            'description' => 'Description before <script>var x = 1; console.log(x);</script> description after',
            'status' => 'pending',
        ]);

        $response->assertStatus(201);
        
        $task = \App\Models\Task::first();
        
        // The script tag and ALL its content should be completely removed
        $this->assertEquals('Before  After', $task->title);
        $this->assertEquals('Description before  description after', $task->description);
        
        // Verify no script content remains
        $this->assertStringNotContainsString('alert', $task->title);
        $this->assertStringNotContainsString('var x', $task->description);
        $this->assertStringNotContainsString('console.log', $task->description);
    }

    public function test_xss_event_handlers_removed(): void
    {
        $user = $this->actingAsUser();

        $response = $this->postJson('/api/tasks', [
            'title' => 'Normal onclick="alert(\'xss\')" Title',
            'description' => 'Description with onmouseover="maliciousCode()" content',
            'status' => 'pending',
        ]);

        $response->assertStatus(201);
        
        $task = \App\Models\Task::first();
        
        // Event handlers should be removed
        $this->assertStringNotContainsString('onclick', $task->title);
        $this->assertStringNotContainsString('onmouseover', $task->description);
        
        // But the normal text should remain
        $this->assertEquals('Normal  Title', $task->title);
        $this->assertEquals('Description with  content', $task->description);
    }
}