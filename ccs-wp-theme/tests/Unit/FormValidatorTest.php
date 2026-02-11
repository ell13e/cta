<?php
/**
 * Form Validator Test
 *
 * @package CTA\Tests\Unit
 */

namespace CCS\Tests\Unit;

use PHPUnit\Framework\TestCase;
use CCS\Services\FormValidator;

class FormValidatorTest extends TestCase {
    
    private FormValidator $validator;
    
    protected function setUp(): void {
        $this->validator = new FormValidator();
    }
    
    /** @test */
    public function it_validates_correct_uk_mobile_number(): void {
        $result = $this->validator->validateUkPhone('07123 456789');
        
        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }
    
    /** @test */
    public function it_validates_correct_uk_landline_number(): void {
        $result = $this->validator->validateUkPhone('01622 587343');
        
        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }
    
    /** @test */
    public function it_rejects_too_short_phone_number(): void {
        $result = $this->validator->validateUkPhone('0123456');
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('10-11 digits', $result['error']);
    }
    
    /** @test */
    public function it_rejects_repeating_digits(): void {
        $result = $this->validator->validateUkPhone('01111111111');
        
        $this->assertFalse($result['valid']);
    }
    
    /** @test */
    public function it_validates_valid_name(): void {
        $result = $this->validator->validateName('John Smith');
        
        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }
    
    /** @test */
    public function it_rejects_too_short_name(): void {
        $result = $this->validator->validateName('J');
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('at least 2 characters', $result['error']);
    }
    
    /** @test */
    public function it_rejects_numeric_only_name(): void {
        $result = $this->validator->validateName('12345');
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('valid name', $result['error']);
    }
    
    /** @test */
    public function it_validates_correct_email(): void {
        $result = $this->validator->validateEmail('user@example.com', true);
        
        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }
    
    /** @test */
    public function it_rejects_invalid_email(): void {
        $result = $this->validator->validateEmail('invalid-email', true);
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('valid email', $result['error']);
    }
    
    /** @test */
    public function it_allows_empty_email_when_not_required(): void {
        $result = $this->validator->validateEmail('', false);
        
        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }
    
    /** @test */
    public function it_rejects_empty_email_when_required(): void {
        $result = $this->validator->validateEmail('', true);
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('required', $result['error']);
    }
}
