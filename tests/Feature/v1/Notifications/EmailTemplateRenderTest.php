<?php

namespace Tests\Feature\v1\Notifications;

use App\Notifications\Admin\AccountLockedResetPassword;
use App\Notifications\Admin\LoginOtpMail as AdminLoginOtpMail;
use App\Notifications\Admin\PasswordChangeOtp;
use App\Notifications\Admin\ResetPasswordMail as AdminResetPasswordMail;
use App\Notifications\Admin\StaffRegistration;
use App\Notifications\User\LoginOtpMail as UserLoginOtpMail;
use App\Notifications\User\ResetPasswordMail as UserResetPasswordMail;
use App\Notifications\User\WelcomeMail as UserWelcomeMail;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class EmailTemplateRenderTest extends TestCase
{
    public function test_admin_account_locked_email_renders_cleanly(): void
    {
        $notifiable = (object) ['email' => 'admin@osafe.test'];
        $notification = new AccountLockedResetPassword('John Doe', 'token123', 'Chrome Desktop', 'Chrome 120', 'Lagos, NG', 'Mr.');
        
        $mailMessage = $notification->toMail($notifiable);
        $html = View::make($mailMessage->view, $mailMessage->viewData)->render();

        $this->assertStringContainsString('O SAFE Security', $html);
        $this->assertStringContainsString('John Doe', $html);
        $this->assertStringContainsString('Administrative Access Restricted', $html);
        $this->assertStringContainsString('Chrome Desktop', $html);
        $this->assertStringContainsString('token123', $html);
    }

    public function test_admin_login_otp_email_renders_cleanly(): void
    {
        $notifiable = (object) ['email' => 'admin2@osafe.test'];
        $notification = new AdminLoginOtpMail('123456', 'iPhone 15', 'Abuja, NG', 'Jane Smith', 'Dr.');

        $mailMessage = $notification->toMail($notifiable);
        $html = View::make($mailMessage->view, $mailMessage->viewData)->render();

        $this->assertStringContainsString('123456', $html);
        $this->assertStringContainsString('Jane Smith', $html);
        $this->assertStringContainsString('Verify Administrative Access', $html);
    }

    public function test_admin_password_change_otp_email_renders_cleanly(): void
    {
        $notifiable = (object) ['email' => 'admin3@osafe.test'];
        $notification = new PasswordChangeOtp('tok567', 'Chief', 'Alex Morgan');

        $mailMessage = $notification->toMail($notifiable);
        $html = View::make($mailMessage->view, $mailMessage->viewData)->render();

        $this->assertStringContainsString('Confirm Password Change', $html);
        $this->assertStringContainsString('Alex Morgan', $html);
        $this->assertStringContainsString('tok567', $html);
    }

    public function test_admin_reset_password_email_renders_cleanly(): void
    {
        $notifiable = (object) ['email' => 'admin4@osafe.test'];
        $notification = new AdminResetPasswordMail('token890', 'Sarah Connor', 'Ms.');

        $mailMessage = $notification->toMail($notifiable);
        $html = View::make($mailMessage->view, $mailMessage->viewData)->render();

        $this->assertStringContainsString('Reset Admin Password', $html);
        $this->assertStringContainsString('Sarah Connor', $html);
    }

    public function test_admin_staff_registration_email_renders_cleanly(): void
    {
        $notifiable = (object) ['email' => 'staff1@osafe.test'];
        $notification = new StaffRegistration('Bruce Wayne', 'TempPass#2026', 'Mr.');

        $mailMessage = $notification->toMail($notifiable);
        $html = View::make($mailMessage->view, $mailMessage->viewData)->render();

        $this->assertStringContainsString('TempPass#2026', $html);
        $this->assertStringContainsString('Bruce Wayne', $html);
        $this->assertStringContainsString('O SAFE Security Management Portal', $html);
    }

    public function test_user_login_otp_email_renders_cleanly(): void
    {
        $notifiable = (object) ['email' => 'user1@osafe.test'];
        $notification = new UserLoginOtpMail('654321', 'Samsung S24', 'Lagos, NG', 'Diana Prince', 'Mrs.');

        $mailMessage = $notification->toMail($notifiable);
        $html = View::make($mailMessage->view, $mailMessage->viewData)->render();

        $this->assertStringContainsString('654321', $html);
        $this->assertStringContainsString('Diana Prince', $html);
        $this->assertStringContainsString('Verify Your Account Passcode', $html);
    }

    public function test_user_reset_password_email_renders_cleanly(): void
    {
        $notifiable = (object) ['email' => 'user2@osafe.test'];
        $notification = new UserResetPasswordMail('memtok123', 'Clark Kent', 'Mr.');

        $mailMessage = $notification->toMail($notifiable);
        $html = View::make($mailMessage->view, $mailMessage->viewData)->render();

        $this->assertStringContainsString('Reset Your Password', $html);
        $this->assertStringContainsString('Clark Kent', $html);
    }

    public function test_user_welcome_email_renders_cleanly(): void
    {
        $notifiable = (object) ['email' => 'user3@osafe.test'];
        $notification = new UserWelcomeMail('Barry Allen', 'Mr.', 'barry@osafe.test', 'Allen');

        $mailMessage = $notification->toMail($notifiable);
        $html = View::make($mailMessage->view, $mailMessage->viewData)->render();

        $this->assertStringContainsString('barry@osafe.test', $html);
        $this->assertStringContainsString('Barry Allen', $html);
        $this->assertStringContainsString('Welcome to Next-Gen Safety', $html);
        $this->assertStringNotContainsString('Allen123', $html); // Plaintext password leak prevented
    }

    public function test_device_alert_email_template_renders(): void
    {
        $html = View::make('emails.devices.device-alert', [
            'fullName' => 'Arthur Curry',
            'deviceName' => 'Front Door Guard',
            'serialNumber' => 'SN-998811',
            'eventType' => 'Tamper Detected',
            'zoneName' => 'North Perimeter',
            'alertLevel' => 'CRITICAL',
            'actionUrl' => 'https://osafe.test/devices/SN-998811'
        ])->render();

        $this->assertStringContainsString('Front Door Guard', $html);
        $this->assertStringContainsString('Tamper Detected', $html);
        $this->assertStringContainsString('CRITICAL', $html);
    }

    public function test_subscription_receipt_email_template_renders(): void
    {
        $html = View::make('emails.subscriptions.subscription-receipt', [
            'fullName' => 'Oliver Queen',
            'planName' => 'O SAFE Pro Shield',
            'reference' => 'TXN-88776655',
            'amountFormatted' => '$29.99',
            'provider' => 'Paystack',
            'actionUrl' => 'https://osafe.test/billing'
        ])->render();

        $this->assertStringContainsString('O SAFE Pro Shield', $html);
        $this->assertStringContainsString('$29.99', $html);
        $this->assertStringContainsString('TXN-88776655', $html);
    }

    public function test_family_invitation_email_template_renders(): void
    {
        $html = View::make('emails.family.invitation', [
            'inviteeName' => 'Hal Jordan',
            'inviterName' => 'Carol Ferris',
            'familyName' => 'Ferris Safety Circle',
            'roleName' => 'Member',
            'acceptUrl' => 'https://osafe.test/family/accept?token=123'
        ])->render();

        $this->assertStringContainsString('Hal Jordan', $html);
        $this->assertStringContainsString('Ferris Safety Circle', $html);
        $this->assertStringContainsString('Carol Ferris', $html);
    }

    public function test_support_ticket_email_template_renders(): void
    {
        $html = View::make('emails.support.ticket-updated', [
            'fullName' => 'Victor Stone',
            'ticketId' => 'TCK-554433',
            'subject' => 'Camera Feed Lag',
            'status' => 'Resolved',
            'messageContent' => 'Firmware update v2.4 applied.',
            'ticketUrl' => 'https://osafe.test/support/tickets/TCK-554433'
        ])->render();

        $this->assertStringContainsString('Victor Stone', $html);
        $this->assertStringContainsString('TCK-554433', $html);
        $this->assertStringContainsString('Firmware update v2.4 applied.', $html);
    }
}
