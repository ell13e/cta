<?php
/**
 * Course Booking Controller Test
 *
 * @package CTA\Tests\Unit\Controllers
 */

namespace CCS\Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use CCS\Controllers\CourseBookingController;
use CCS\Services\FormValidator;

class CourseBookingControllerTest extends TestCase {
    
    private CourseBookingController $controller;
    private FormValidator $validator;
    
    protected function setUp(): void {
        $this->validator = new FormValidator();
        $this->controller = new CourseBookingController($this->validator);
    }
    
    /** @test */
    public function it_can_be_instantiated(): void {
        $controller = new CourseBookingController();
        
        $this->assertInstanceOf(CourseBookingController::class, $controller);
    }
    
    /** @test */
    public function it_accepts_validator_via_constructor(): void {
        $validator = new FormValidator();
        $controller = new CourseBookingController($validator);
        
        $this->assertInstanceOf(CourseBookingController::class, $controller);
    }
    
    /** @test */
    public function it_has_handle_method(): void {
        $this->assertTrue(method_exists($this->controller, 'handle'));
    }
    
    /**
     * Note: Full integration testing of handle() method requires WordPress test environment
     * as it calls wp_verify_nonce(), wp_send_json_error(), etc. which exit execution.
     * These tests verify the controller structure and dependency injection.
     * Validation logic is tested via FormValidatorTest.
     */
}
