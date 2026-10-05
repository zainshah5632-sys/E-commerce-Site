<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;

final class PageController
{
    private const PAGES = [
        'about' => [
            'title'   => 'About GlassCart',
            'summary' => 'Merging physical industrial precision with transparent digital commerce.',
            'content' => [
                'Our Philosophy' => 'GlassCart was founded on the belief that everyday tools and audio instruments should balance uncompromised utility with sculptural elegance. We curate hardware from independent artisans and design-driven manufacturers who treat materials with reverence.',
                'The Aesthetic'  => 'Inspired by translucent interfaces and raw acoustic clarity, every product in our collection undergoes rigorous ergonomic testing before joining our catalog.',
                'Sustainability' => 'We package all shipments in 100% recyclable unbleached kraft cardboard, using soy-based inks and zero single-use virgin plastics.',
            ],
        ],
        'contact' => [
            'title'   => 'Contact & Concierge',
            'summary' => 'Our support team is available Monday through Friday, 9:00 AM – 6:00 PM EST.',
            'content' => [
                'Inquiries'       => 'Have a question about a product or looking for order status? Reach out to our concierge team at hello@glasscart.example.com.',
                'Press & Studio'  => 'For editorial loans and media partnerships, contact press@glasscart.example.com.',
                'Headquarters'    => 'GlassCart Design Studio, 420 Translucent Way, Suite 800, New York, NY 10013.',
            ],
        ],
        'shipping' => [
            'title'   => 'Shipping & Delivery',
            'summary' => 'Fast, insured global fulfillment from our climate-controlled studio.',
            'content' => [
                'Domestic Rates'     => 'Standard ground shipping is $5.00 for orders under $75.00. Orders totaling $75.00 or more qualify for complimentary standard shipping.',
                'Fulfillment Times'  => 'Orders placed before 2:00 PM EST ship the same business day. Delivery typically takes 2–4 business days within the continental United States.',
                'Tracking & Transit' => 'As soon as your shipment is scanned by the courier, you will receive an email confirmation with real-time tracking coordinates.',
            ],
        ],
        'returns' => [
            'title'   => 'Returns & Exchanges',
            'summary' => '30-day hassle-free returns on all unopened or manufacturer-defective hardware.',
            'content' => [
                'Return Window'     => 'We accept returns within 30 days of delivery. Items must be in original, unblemished condition with all documentation and accessories intact.',
                'How to Initiate'   => 'Contact our concierge with your order number to receive a pre-paid printable shipping label.',
                'Refund Processing' => 'Once inspected by our warehouse technicians, refunds are credited to your original payment method within 3–5 business days.',
            ],
        ],
        'privacy' => [
            'title'   => 'Privacy Policy',
            'summary' => 'Your personal data is never sold, rented, or tracked across third-party networks.',
            'content' => [
                'Data Collection'  => 'We collect only the essential details required to process transactions and fulfill orders: name, shipping address, contact email, and telephone number.',
                'Security Storage' => 'Sessions are encrypted client-side using industry-standard libsodium cryptography. Payment information is processed through secure, tokenized protocols.',
                'Your Rights'      => 'You may request an export or complete erasure of your profile and order history at any time by contacting our data protection officer.',
            ],
        ],
        'terms' => [
            'title'   => 'Terms of Service',
            'summary' => 'Standard terms governing your use of the GlassCart platform and store.',
            'content' => [
                'Account Terms'     => 'You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account.',
                'Product Inventory' => 'All product listings are subject to real-time warehouse availability. In the event an item becomes unavailable after an order is placed, we will promptly refund the full purchase price.',
                'Governing Law'     => 'These terms shall be governed and construed in accordance with the laws of the State of New York, without regard to conflict of law provisions.',
            ],
        ],
    ];

    public function show(Request $request): Response
    {
        $slug = (string)$request->param('slug');
        if (!isset(self::PAGES[$slug])) {
            return Response::html(View::render('pages/404', ['title' => 'Page Not Found']), 404);
        }

        $page = self::PAGES[$slug];
        return Response::html(View::render('pages/static', [
            'title' => "{$page['title']} — GlassCart",
            'slug'  => $slug,
            'page'  => $page,
        ]));
    }
}
