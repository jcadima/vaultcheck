<?php

declare(strict_types=1);

use VaultCheck\Patterns\SecretPattern;

/**
 * Registry of known secret patterns.
 * Each entry is a SecretPattern describing a service's credential format.
 *
 * @return SecretPattern[]
 */
return [
    // ─── Stripe ──────────────────────────────────────────────────────────────
    new SecretPattern(
        name: 'Stripe Secret Key (live)',
        regex: '/\bsk_live_[0-9a-zA-Z]{24,}\b/',
        service: 'stripe',
        rotationUrl: 'https://dashboard.stripe.com/apikeys',
    ),
    new SecretPattern(
        name: 'Stripe Secret Key (test)',
        regex: '/\bsk_test_[0-9a-zA-Z]{24,}\b/',
        service: 'stripe',
        rotationUrl: 'https://dashboard.stripe.com/apikeys',
    ),
    new SecretPattern(
        name: 'Stripe Publishable Key (live)',
        regex: '/\bpk_live_[0-9a-zA-Z]{24,}\b/',
        service: 'stripe',
        rotationUrl: 'https://dashboard.stripe.com/apikeys',
    ),
    new SecretPattern(
        name: 'Stripe Publishable Key (test)',
        regex: '/\bpk_test_[0-9a-zA-Z]{24,}\b/',
        service: 'stripe',
        rotationUrl: 'https://dashboard.stripe.com/apikeys',
    ),
    new SecretPattern(
        name: 'Stripe Webhook Secret',
        regex: '/\bwhsec_[0-9a-zA-Z]{32,}\b/',
        service: 'stripe',
        rotationUrl: 'https://dashboard.stripe.com/webhooks',
    ),
    new SecretPattern(
        name: 'Stripe Restricted Key',
        regex: '/\brk_live_[0-9a-zA-Z]{24,}\b/',
        service: 'stripe',
        rotationUrl: 'https://dashboard.stripe.com/apikeys',
    ),

    // ─── AWS ─────────────────────────────────────────────────────────────────
    new SecretPattern(
        name: 'AWS Access Key ID',
        regex: '/\bAKIA[0-9A-Z]{16}\b/',
        service: 'aws',
        rotationUrl: 'https://console.aws.amazon.com/iam/home#/security_credentials',
    ),
    new SecretPattern(
        name: 'AWS Secret Access Key',
        regex: '/(?:aws.{0,20})?(?:secret|access).{0,20}[\'"]?([0-9a-zA-Z\/+]{40})[\'"]?/i',
        service: 'aws',
        rotationUrl: 'https://console.aws.amazon.com/iam/home#/security_credentials',
    ),

    // ─── GitHub ───────────────────────────────────────────────────────────────
    new SecretPattern(
        name: 'GitHub Personal Access Token',
        regex: '/\bghp_[a-zA-Z0-9]{36}\b/',
        service: 'github',
        rotationUrl: 'https://github.com/settings/tokens',
    ),
    new SecretPattern(
        name: 'GitHub Fine-Grained Token',
        regex: '/\bgithub_pat_[a-zA-Z0-9_]{82}\b/',
        service: 'github',
        rotationUrl: 'https://github.com/settings/tokens',
    ),
    new SecretPattern(
        name: 'GitHub OAuth Token',
        regex: '/\bgho_[a-zA-Z0-9]{36}\b/',
        service: 'github',
        rotationUrl: 'https://github.com/settings/applications',
    ),
    new SecretPattern(
        name: 'GitHub Actions Secret',
        regex: '/\bghs_[a-zA-Z0-9]{36}\b/',
        service: 'github',
        rotationUrl: 'https://github.com/settings/tokens',
    ),

    // ─── Google ───────────────────────────────────────────────────────────────
    new SecretPattern(
        name: 'Google API Key',
        regex: '/\bAIza[0-9A-Za-z\-_]{35}\b/',
        service: 'google',
        rotationUrl: 'https://console.cloud.google.com/apis/credentials',
    ),
    new SecretPattern(
        name: 'Google OAuth Client Secret',
        regex: '/GOCSPX-[a-zA-Z0-9_\-]{28}/',
        service: 'google',
        rotationUrl: 'https://console.cloud.google.com/apis/credentials',
    ),

    // ─── Twilio ───────────────────────────────────────────────────────────────
    new SecretPattern(
        name: 'Twilio API Key',
        regex: '/\bSK[0-9a-fA-F]{32}\b/',
        service: 'twilio',
        rotationUrl: 'https://www.twilio.com/console/voice/settings/api-keys',
    ),
    new SecretPattern(
        name: 'Twilio Account SID',
        regex: '/\bAC[0-9a-fA-F]{32}\b/',
        service: 'twilio',
        rotationUrl: 'https://www.twilio.com/console',
    ),

    // ─── SendGrid ─────────────────────────────────────────────────────────────
    new SecretPattern(
        name: 'SendGrid API Key',
        regex: '/\bSG\.[a-zA-Z0-9]{22}\.[a-zA-Z0-9]{43}\b/',
        service: 'sendgrid',
        rotationUrl: 'https://app.sendgrid.com/settings/api_keys',
    ),

    // ─── Slack ────────────────────────────────────────────────────────────────
    new SecretPattern(
        name: 'Slack Bot Token',
        regex: '/\bxoxb-[0-9]{10,13}-[0-9]{10,13}-[a-zA-Z0-9]{24,}\b/',
        service: 'slack',
        rotationUrl: 'https://api.slack.com/apps',
    ),
    new SecretPattern(
        name: 'Slack User Token',
        regex: '/\bxoxp-[0-9]{10,13}-[a-zA-Z0-9\-]+/',
        service: 'slack',
        rotationUrl: 'https://api.slack.com/apps',
    ),
    new SecretPattern(
        name: 'Slack App-Level Token',
        regex: '/\bxapp-\d-[A-Z0-9]+-\d+-[a-f0-9]+\b/',
        service: 'slack',
        rotationUrl: 'https://api.slack.com/apps',
    ),

    // ─── Mailgun ──────────────────────────────────────────────────────────────
    new SecretPattern(
        name: 'Mailgun API Key',
        regex: '/\bkey-[0-9a-zA-Z]{32}\b/',
        service: 'mailgun',
        rotationUrl: 'https://app.mailgun.com/app/account/security/api_keys',
    ),

    // ─── DigitalOcean ─────────────────────────────────────────────────────────
    new SecretPattern(
        name: 'DigitalOcean Personal Access Token',
        regex: '/\bdop_v1_[a-z0-9]{64}\b/',
        service: 'digitalocean',
        rotationUrl: 'https://cloud.digitalocean.com/account/api/tokens',
    ),

    // ─── JWT ──────────────────────────────────────────────────────────────────
    new SecretPattern(
        name: 'JSON Web Token',
        regex: '/\beyJ[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]+\b/',
        service: 'jwt',
        rotationUrl: '',
    ),

    // ─── PEM / Private Keys ───────────────────────────────────────────────────
    new SecretPattern(
        name: 'PEM Private Key',
        regex: '/-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----/',
        service: 'pki',
        rotationUrl: '',
    ),

    // ─── Heroku ───────────────────────────────────────────────────────────────
    new SecretPattern(
        name: 'Heroku API Key',
        regex: '/(?i)heroku.{0,20}[\'"]?([0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12})[\'"]?/',
        service: 'heroku',
        rotationUrl: 'https://dashboard.heroku.com/account',
    ),

    // ─── Mailchimp ────────────────────────────────────────────────────────────
    new SecretPattern(
        name: 'Mailchimp API Key',
        regex: '/\b[0-9a-f]{32}-us[0-9]{1,2}\b/',
        service: 'mailchimp',
        rotationUrl: 'https://us1.admin.mailchimp.com/account/api/',
    ),

    // ─── npm ──────────────────────────────────────────────────────────────────
    new SecretPattern(
        name: 'npm Access Token',
        regex: '/\bnpm_[a-zA-Z0-9]{36}\b/',
        service: 'npm',
        rotationUrl: 'https://www.npmjs.com/settings/~/tokens',
    ),

    // ─── PyPI ─────────────────────────────────────────────────────────────────
    new SecretPattern(
        name: 'PyPI API Token',
        regex: '/\bpypi-AgEIcHlwaS5vcmc[a-zA-Z0-9\-_]+\b/',
        service: 'pypi',
        rotationUrl: 'https://pypi.org/manage/account/token/',
    ),

    // ─── Shopify ──────────────────────────────────────────────────────────────
    new SecretPattern(
        name: 'Shopify Access Token',
        regex: '/\bshpat_[a-fA-F0-9]{32}\b/',
        service: 'shopify',
        rotationUrl: 'https://partners.shopify.com/',
    ),
    new SecretPattern(
        name: 'Shopify Private App Password',
        regex: '/\bshppa_[a-fA-F0-9]{32}\b/',
        service: 'shopify',
        rotationUrl: 'https://partners.shopify.com/',
    ),

    // ─── Generic credential assignment ────────────────────────────────────────
    new SecretPattern(
        name: 'Generic Password Assignment',
        regex: '/(?i)password\s*[=:]\s*[\'"]?(?!changeme|password|example|placeholder|your[-_]?password)[^\s\'"]{8,}[\'"]?/',
        service: 'generic',
        rotationUrl: '',
    ),
    new SecretPattern(
        name: 'Generic Secret Assignment',
        regex: '/(?i)(?:secret|api_?key|auth_?token)\s*[=:]\s*[\'"]?(?!changeme|example|placeholder|your[-_])[^\s\'"]{12,}[\'"]?/',
        service: 'generic',
        rotationUrl: '',
    ),
];
