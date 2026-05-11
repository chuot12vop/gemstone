@extends('layouts.shop')

@section('content')
<header class="page-head">
    <h1 class="page-head__title">About Our {{ config('app.name') }}</h1>
    <p class="page-head__summary">More than jewelry — a bridge between mindful tradition and contemporary life.</p>
</header>
<div class="prose">
    <div class="guardian-collection-container">
        <div class="header-section">
            <h2>Choose pieces crafted to support confidence and prosperity in daily life.</h2>
            <p>Wealth is more than just numbers in a bank account; it is an active, flowing energy. It moves toward intention, confidence, and clarity. However, in the chaos of modern life, maintaining that focused energy can be challenging. This is where your Sacred Guardian steps in.</p>
        </div>
    
        <div class="brand-intro">
            <p>At <strong>Tachi Gem Stone</strong>, our guardian pieces—such as the elegantly carved Sacred Fox—are designed to be more than just captivating jewelry. They serve as spiritual anchors with a powerful dual purpose:</p>
        </div>
    
        <div class="purpose-list">
            <div class="purpose-item attract">
                <p><strong>• To Attract (The Magnet):</strong> A Sacred Guardian works by elevating your personal frequency. It enhances your natural charisma, boosts your self-confidence in negotiations, and acts as a magnet for new opportunities, prosperous connections, and abundance. It reminds you of your worth every time it rests against your skin.</p>
            </div>
            <div class="purpose-item protect">
                <p><strong>• To Protect (The Shield):</strong> True prosperity requires safeguarding what you’ve built. These carefully chosen gemstones act as an energetic shield, protecting you from "energy vampires," impulsive financial decisions, and the negativity of those who might drain your momentum.</p>
            </div>
        </div>
    
        <div class="selection-guide">
            <h3>How to Choose Your Guardian:</h3>
            <p>Look through our collection and pay attention to where your eyes linger. Does a warm, golden stone call to your desire for stability? Or does a vibrant, deep-toned gem speak to your need for fierce protection?</p>
        </div>
    
        <div class="footer-note">
            <p>Trust your intuition. When you select the guardian that resonates with your current journey, you aren't just wearing a beautiful gemstone—you are welcoming a silent, steadfast partner dedicated to your daily prosperity and peace of mind.</p>
        </div>
    </div>
</div>
@endsection
