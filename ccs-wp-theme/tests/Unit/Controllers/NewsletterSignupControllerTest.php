<?php
/**
 * Newsletter Signup Controller Test
 *
 * @package CTA\Tests\Unit\Controllers
 */

namespace CCS\Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use CCS\Controllers\NewsletterSignupController;
use CCS\Services\FormValidator;
use CCS\Repositories\FormSubmissionRepository;

class NewsletterSignupControllerTest extends TestCase {
    
    private NewsletterSignupController $controller;
    private FormValidator $validator;
    private FormSubmissionRepository $repository;
    
    protected function setUp(): void {
        $this->validator = new FormValidator();
        $this->repository = new FormSubmissionRepository();
        $this->controller = new NewsletterSignupController($this->validator, $this->repository);
    }
    
    /** @test */
    public function it_can_be_instantiated(): void {
        $controller = new NewsletterSignupController();
        
        $this->assertInstanceOf(NewsletterSignupController::class, $controller);
    }
    
    /** @test */
    public function it_accepts_validator_and_repository_via_constructor(): void {
        $validator = new FormValidator();
        $repository = new FormSubmissionRepository();
        $controller = new NewsletterSignupController($validator, $repository);
        
        $this->assertInstanceOf(NewsletterSignupController::class, $controller);
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
