@extends('backend_layouts.guest')
@section('title')
    <title>{{ __('Login') }}</title>
@endsection

@push('css')
<style>
    /* Reset & Base */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        background: #f0f4f8;
        min-height: 100vh;
        overflow: hidden;
    }

    /* Main Container - Split Layout */
    .login-container {
        display: flex;
        min-height: 100vh;
        background: #f0f4f8;
        position: relative;
    }

    /* LEFT SIDE - Animation Area */
    .login-animation {
        flex: 1;
        background: linear-gradient(135deg, #0a1628 0%, #1a2a4a 40%, #1a3a6b 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px;
        position: relative;
        overflow: hidden;
    }

    /* Animated Background Pattern */
    .login-animation::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-image: 
            radial-gradient(circle at 20% 50%, rgba(26, 58, 107, 0.4) 0%, transparent 50%),
            radial-gradient(circle at 80% 50%, rgba(196, 30, 58, 0.15) 0%, transparent 50%),
            linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
        background-size: 100% 100%, 100% 100%, 60px 60px, 60px 60px;
        animation: gridFloat 20s linear infinite;
    }

    @keyframes gridFloat {
        0% { transform: translateY(0px) rotate(0deg); }
        100% { transform: translateY(-60px) rotate(0deg); }
    }

    /* Life Signal Animation Container */
    .life-signal-wrapper {
        position: relative;
        z-index: 2;
        width: 100%;
        max-width: 500px;
        padding: 20px;
    }

    .life-signal-content {
        text-align: center;
        color: #ffffff;
    }

    /* Logo / Brand */
    .brand-icon {
        font-size: 64px;
        color: rgba(255, 255, 255, 0.9);
        margin-bottom: 30px;
        display: inline-block;
        animation: pulseIcon 3s ease-in-out infinite;
    }

    @keyframes pulseIcon {
        0%, 100% { transform: scale(1); opacity: 0.9; }
        50% { transform: scale(1.05); opacity: 1; }
    }

    .brand-title {
        font-size: 32px;
        font-weight: 700;
        color: #ffffff;
        margin-bottom: 10px;
        letter-spacing: -0.5px;
    }

    .brand-subtitle {
        font-size: 16px;
        color: rgba(255, 255, 255, 0.6);
        margin-bottom: 40px;
        font-weight: 300;
    }

    /* Life Signal Line - Main Animation */
    .signal-container {
        width: 100%;
        max-width: 400px;
        margin: 0 auto 30px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 8px;
        padding: 20px 10px 10px;
        position: relative;
        border: 1px solid rgba(255, 255, 255, 0.06);
    }

    .signal-line-wrapper {
        position: relative;
        height: 80px;
        background: rgba(0, 0, 0, 0.2);
        border-radius: 4px;
        overflow: hidden;
    }

    /* ECG Grid Lines */
    .signal-grid {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-image: 
            linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
        background-size: 20px 20px, 20px 20px;
    }

    /* ECG Signal Path */
    .signal-path {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
    }

    .signal-path svg {
        width: 100%;
        height: 100%;
    }

    .signal-path polyline {
        fill: none;
        stroke: #e63946;
        stroke-width: 3;
        stroke-linecap: round;
        stroke-linejoin: round;
        stroke-dasharray: 1000;
        stroke-dashoffset: 1000;
        animation: drawSignal 4s ease-in-out infinite;
        filter: drop-shadow(0 0 6px rgba(230, 57, 70, 0.4));
    }

    /* Secondary signal (faint) */
    .signal-path .signal-secondary {
        stroke: rgba(26, 58, 107, 0.6);
        stroke-width: 2;
        stroke-dasharray: 1000;
        stroke-dashoffset: 1000;
        animation: drawSignal 4s ease-in-out infinite 0.5s;
        filter: drop-shadow(0 0 10px rgba(26, 58, 107, 0.3));
    }

    @keyframes drawSignal {
        0% { stroke-dashoffset: 1000; }
        50% { stroke-dashoffset: 0; }
        100% { stroke-dashoffset: -1000; }
    }

    /* Signal Glow Point */
    .signal-dot {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        width: 8px;
        height: 8px;
        background: #e63946;
        border-radius: 50%;
        box-shadow: 0 0 20px rgba(230, 57, 70, 0.6);
        animation: dotPulse 1.5s ease-in-out infinite;
    }

    .signal-dot-secondary {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        width: 20px;
        height: 20px;
        background: rgba(230, 57, 70, 0.15);
        border-radius: 50%;
        animation: dotPulse 1.5s ease-in-out infinite 0.3s;
    }

    @keyframes dotPulse {
        0%, 100% { transform: translateY(-50%) scale(1); opacity: 1; }
        50% { transform: translateY(-50%) scale(1.5); opacity: 0.8; }
    }

    /* Signal Labels */
    .signal-labels {
        display: flex;
        justify-content: space-between;
        margin-top: 8px;
        padding: 0 4px;
        font-size: 10px;
        color: rgba(255, 255, 255, 0.3);
        font-weight: 500;
        letter-spacing: 1px;
    }

    /* Heart Rate Display */
    .heart-rate {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin-top: 20px;
        color: rgba(255, 255, 255, 0.8);
    }

    .heart-rate .heart-icon {
        color: #e63946;
        font-size: 20px;
        animation: heartBeat 1.2s ease-in-out infinite;
    }

    @keyframes heartBeat {
        0%, 100% { transform: scale(1); }
        14% { transform: scale(1.3); }
        28% { transform: scale(1); }
        42% { transform: scale(1.2); }
        70% { transform: scale(1); }
    }

    .heart-rate .rate-value {
        font-size: 28px;
        font-weight: 700;
        color: #ffffff;
        letter-spacing: 1px;
    }

    .heart-rate .rate-unit {
        font-size: 14px;
        color: rgba(255, 255, 255, 0.5);
        font-weight: 300;
    }

    /* Floating Stats */
    .stats-row {
        display: flex;
        justify-content: center;
        gap: 40px;
        margin-top: 35px;
    }

    .stat-item {
        text-align: center;
    }

    .stat-item .stat-number {
        font-size: 22px;
        font-weight: 700;
        color: #ffffff;
        display: block;
    }

    .stat-item .stat-label {
        font-size: 11px;
        color: rgba(255, 255, 255, 0.4);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 500;
    }

    /* RIGHT SIDE - Login Card */
    .login-form-side {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px;
        background: #f0f4f8;
    }

    /* Login Card - Modal Style */
    .login-card {
        background: #ffffff;
        border-radius: 24px;
        box-shadow: 
            0 20px 60px rgba(0, 0, 0, 0.08),
            0 8px 20px rgba(0, 0, 0, 0.04);
        padding: 48px 40px;
        width: 100%;
        max-width: 420px;
        position: relative;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .login-card:hover {
        transform: translateY(-4px);
        box-shadow: 
            0 30px 80px rgba(0, 0, 0, 0.1),
            0 10px 25px rgba(0, 0, 0, 0.05);
    }

    /* Card Accent Line */
    .login-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 4px;
        background: linear-gradient(90deg, #1a3a6b, #c41e3a, #1a3a6b);
        border-radius: 0 0 4px 4px;
        animation: accentPulse 3s ease-in-out infinite;
    }

    @keyframes accentPulse {
        0%, 100% { width: 80px; opacity: 0.7; }
        50% { width: 120px; opacity: 1; }
    }

    /* Card Header */
    .login-card-header {
        text-align: center;
        margin-bottom: 32px;
    }

    .login-card-header .brand-logo {
        max-height: 45px;
        width: auto;
        margin-bottom: 16px;
    }

    .login-card-header h2 {
        font-size: 24px;
        font-weight: 700;
        color: #0a1628;
        margin: 0 0 6px;
        letter-spacing: -0.5px;
    }

    .login-card-header p {
        color: #6b7a8f;
        font-size: 14px;
        margin: 0;
        font-weight: 400;
    }

    /* Form Elements */
    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #1a2a4a;
        margin-bottom: 6px;
        letter-spacing: 0.3px;
    }

    .form-group .input-group {
        position: relative;
    }

    .form-group .input-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #9aa8b9;
        font-size: 16px;
        transition: color 0.3s ease;
        pointer-events: none;
    }

    .form-control {
        width: 100%;
        padding: 12px 16px 12px 44px;
        border: 2px solid #e8edf2;
        border-radius: 12px;
        font-size: 14px;
        color: #1a2a4a;
        background: #f8fafc;
        transition: all 0.3s ease;
        outline: none;
        height: 48px;
    }

    .form-control:focus {
        border-color: #1a3a6b;
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(26, 58, 107, 0.08);
    }

    .form-control::placeholder {
        color: #b0bec5;
        font-size: 14px;
    }

    .form-control.is-invalid {
        border-color: #e63946;
    }

    .form-control.is-invalid:focus {
        box-shadow: 0 0 0 4px rgba(230, 57, 70, 0.08);
    }

    .invalid-feedback {
        color: #e63946;
        font-size: 12px;
        margin-top: 4px;
        display: block;
    }

    /* Login Options Row */
    .login-options {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 22px 0;
    }

    .form-check {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }

    .form-check-input {
        width: 18px;
        height: 18px;
        border: 2px solid #d0d7de;
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.2s ease;
        appearance: none;
        -webkit-appearance: none;
        position: relative;
        flex-shrink: 0;
        background: #ffffff;
    }

    .form-check-input:checked {
        background: #1a3a6b;
        border-color: #1a3a6b;
    }

    .form-check-input:checked::after {
        content: '✓';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: #ffffff;
        font-size: 11px;
        font-weight: 700;
    }

    .form-check-input:focus {
        box-shadow: 0 0 0 3px rgba(26, 58, 107, 0.15);
    }

    .form-check-label {
        font-size: 13px;
        color: #4a5a6e;
        cursor: pointer;
        font-weight: 500;
    }

    .forgot-link {
        font-size: 13px;
        color: #1a3a6b;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.3s ease;
    }

    .forgot-link:hover {
        color: #c41e3a;
        text-decoration: none;
    }

    /* Login Button */
    .btn-login {
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, #1a3a6b 0%, #0a1628 100%);
        border: none;
        border-radius: 12px;
        color: #ffffff;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        height: 50px;
        letter-spacing: 0.3px;
    }

    .btn-login::after {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
        transition: left 0.6s ease;
    }

    .btn-login:hover::after {
        left: 100%;
    }

    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(26, 58, 107, 0.3);
    }

    .btn-login:active {
        transform: translateY(0);
    }

    .btn-login:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    /* Responsive */
    @media (max-width: 992px) {
        .login-container {
            flex-direction: column;
        }

        .login-animation {
            padding: 30px 20px;
            min-height: 45vh;
        }

        .login-form-side {
            padding: 20px;
            min-height: auto;
        }

        .login-card {
            padding: 32px 24px;
            max-width: 400px;
        }

        .brand-title {
            font-size: 24px;
        }

        .signal-container {
            max-width: 320px;
        }

        .stats-row {
            gap: 20px;
        }
    }

    @media (max-width: 576px) {
        .login-animation {
            min-height: 35vh;
            padding: 20px 15px;
        }

        .brand-title {
            font-size: 20px;
        }

        .brand-subtitle {
            font-size: 13px;
            margin-bottom: 20px;
        }

        .signal-container {
            padding: 12px 6px 6px;
        }

        .signal-line-wrapper {
            height: 60px;
        }

        .login-card {
            padding: 24px 16px;
            border-radius: 16px;
        }

        .login-card-header h2 {
            font-size: 20px;
        }

        .login-options {
            flex-direction: column;
            gap: 12px;
            align-items: flex-start;
        }

        .stats-row {
            gap: 15px;
        }

        .stat-item .stat-number {
            font-size: 18px;
        }

        .heart-rate .rate-value {
            font-size: 22px;
        }

        .login-form-side {
            padding: 12px;
        }
    }

    /* Small height screens */
    @media (max-height: 700px) {
        .login-animation {
            min-height: 30vh;
        }
        .brand-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
        .brand-title {
            font-size: 20px;
            margin-bottom: 4px;
        }
        .brand-subtitle {
            font-size: 12px;
            margin-bottom: 15px;
        }
        .signal-container {
            max-width: 280px;
        }
        .signal-line-wrapper {
            height: 50px;
        }
        .stats-row {
            margin-top: 15px;
            gap: 15px;
        }
        .stat-item .stat-number {
            font-size: 16px;
        }
    }
</style>
@endpush

@section('content')
<div class="login-container">
    
    <!-- LEFT: Animation Side -->
    <div class="login-animation">
        <div class="life-signal-wrapper">
            <div class="life-signal-content">
                
                <!-- Brand Icon -->
                <div class="brand-icon">
                    <i class="fas fa-heartbeat"></i>
                </div>

                <h1 class="brand-title">{{ $setting?->app_name ?? 'MediCare' }}</h1>
                <p class="brand-subtitle">{{ __('Healthcare Management System') }}</p>

                <!-- Life Signal Animation -->
                <div class="signal-container">
                    <div class="signal-line-wrapper">
                        <div class="signal-grid"></div>
                        
                        <!-- Primary Signal -->
                        <svg class="signal-path" viewBox="0 0 400 80" preserveAspectRatio="none">
                            <polyline points="0,40 20,40 30,65 40,15 50,40 60,40 70,40 80,40 90,40 100,40 110,40 120,40 130,40 140,40 150,40 160,40 170,40 180,40 190,40 200,40 210,40 220,40 230,40 240,40 250,40 260,40 270,40 280,40 290,40 300,40 310,40 320,40 330,40 340,40 350,40 360,40 370,40 380,40 390,40 400,40" />
                            <polyline class="signal-secondary" points="0,40 20,40 25,50 30,30 35,45 40,38 45,42 50,40 60,40 70,40 80,40 90,40 100,40 110,40 120,40 130,40 140,40 150,40 160,40 170,40 180,40 190,40 200,40 210,40 220,40 230,40 240,40 250,40 260,40 270,40 280,40 290,40 300,40 310,40 320,40 330,40 340,40 350,40 360,40 370,40 380,40 390,40 400,40" />
                        </svg>

                        <!-- ECG Signal Dots -->
                        <div class="signal-dot-secondary"></div>
                        <div class="signal-dot"></div>
                    </div>
                    
                    <div class="signal-labels">
                        <span>HR</span>
                        <span>SPO2</span>
                        <span>BP</span>
                        <span>ECG</span>
                    </div>
                </div>

                <!-- Heart Rate Display -->
                <div class="heart-rate">
                    <span class="heart-icon"><i class="fas fa-heart"></i></span>
                    <span class="rate-value">72</span>
                    <span class="rate-unit">{{ __('BPM') }}</span>
                </div>

                <!-- Stats -->
                <div class="stats-row">
                    <div class="stat-item">
                        <span class="stat-number">24/7</span>
                        <span class="stat-label">{{ __('Support') }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">100%</span>
                        <span class="stat-label">{{ __('Secure') }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">50K+</span>
                        <span class="stat-label">{{ __('Patients') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT: Login Form -->
    <div class="login-form-side">
        <div class="login-card">
            
            <div class="login-card-header">
                <img src="{{ asset($setting?->logo) }}" alt="{{ $setting?->app_name }}" class="brand-logo">
                <h2>{{ __('Welcome Back') }}</h2>
                <p>{{ __('Login to access your dashboard') }}</p>
            </div>

            <form id="adminLoginForm" action="{{ route('admin.store-login') }}" method="POST">
                @csrf

                <!-- Email -->
                <div class="form-group">
                    <label for="email">{{ __('Email Address') }}</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-envelope"></i></span>
                        @if (app()->isLocal() && app()->hasDebugModeEnabled())
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   value="admin@gmail.com" 
                                   required 
                                   autofocus
                                   placeholder="Enter your email">
                        @else
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   value="{{ old('email') }}" 
                                   required 
                                   autofocus
                                   placeholder="Enter your email">
                        @endif
                    </div>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password">{{ __('Password') }}</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-lock"></i></span>
                        @if (app()->isLocal() && app()->hasDebugModeEnabled())
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="form-control @error('password') is-invalid @enderror" 
                                   value="1234" 
                                   required
                                   placeholder="Enter your password">
                        @else
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="form-control @error('password') is-invalid @enderror" 
                                   required
                                   placeholder="Enter your password">
                        @endif
                    </div>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Options -->
                <div class="login-options">
                    <label class="form-check">
                        <input type="checkbox" name="remember" class="form-check-input" {{ old('remember') ? 'checked' : '' }}>
                        <span class="form-check-label">{{ __('Remember Me') }}</span>
                    </label>
                    <a href="{{ route('admin.password.request') }}" class="forgot-link">
                        {{ __('Forgot Password?') }}
                    </a>
                </div>

                <!-- Submit -->
                <button type="submit" class="btn-login" id="loginBtn">
                    <i class="fas fa-sign-in-alt me-2"></i>{{ __('Login') }}
                </button>
            </form>

        </div>
    </div>

</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('adminLoginForm');
        const submitBtn = document.getElementById('loginBtn');

        form.addEventListener('submit', function(e) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>{{ __("Logging in...") }}';
        });

        // Animate heart rate number (demo)
        let rate = 72;
        setInterval(() => {
            const rateEl = document.querySelector('.rate-value');
            if (rateEl) {
                const change = Math.floor(Math.random() * 6) - 2;
                rate = Math.max(60, Math.min(85, rate + change));
                rateEl.textContent = rate;
            }
        }, 3000);
    });
</script>
@endpush

@endsection