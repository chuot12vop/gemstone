@extends('layouts.shop')

@section('content')
<header class="page-head">
    <h1 class="page-head__title">About Our {{ config('app.name') }}</h1>
    <p class="page-head__summary">More than jewelry — a bridge between mindful tradition and contemporary life.</p>
</header>
<div class="prose">
    <div class="five-elements-container">
        <div class="intro-section">
            <p>The universe thrives on balance, and so do we. The ancient philosophy of the Five Elements (Wu Xing)—Wood, Fire, Earth, Metal, and Water—is a profound map to understanding our inner energy. In this tradition, everything in nature is connected, and by aligning your personal energy with the right gemstone, you can invite harmony, protection, and prosperity into your modern life.</p>
            <p>Discover which element your soul is calling for and find the gemstone that speaks to you:</p>
        </div>
    
        <div class="element-item wood">
            <h3>🌿 WOOD (The Seeker of Growth)</h3>
            <p><strong>The Energy:</strong> Vitality, healing, and new beginnings. Like a tree reaching for the sun, Wood energy brings forward momentum and constant personal growth.</p>
            <p><strong>When you need it:</strong> If you feel stuck, are stepping into a new chapter of life, or need physical and emotional healing.</p>
            <p><strong>Gemstone Match:</strong> Embrace green gemstones like Jade, Green Aventurine, or Malachite to invite fresh opportunities and vibrant health.</p>
        </div>
    
        <div class="element-item fire">
            <h3>🔥 FIRE (The Spark of Passion)</h3>
            <p><strong>The Energy:</strong> Transformation, love, and dynamic action. Fire is the ultimate catalyst, radiating warmth, confidence, and magnetic charisma.</p>
            <p><strong>When you need it:</strong> When you want to reignite your inner drive, attract deep romantic connections, or stand out in your career.</p>
            <p><strong>Gemstone Match:</strong> Choose fiery tones like Red Garnet, Ruby, or vibrant Amethyst to awaken your inner strength and manifest your desires.</p>
        </div>
    
        <div class="element-item earth">
            <h3>⛰️ EARTH (The Grounded Anchor)</h3>
            <p><strong>The Energy:</strong> Stability, nourishment, and profound peace. Earth energy is the solid ground beneath your feet, offering a sense of safety and abundance.</p>
            <p><strong>When you need it:</strong> In a fast-paced world, if you feel anxious, scattered, or are looking to build long-term financial security and a harmonious home.</p>
            <p><strong>Gemstone Match:</strong> Connect with warm, grounding stones like Citrine, Tiger’s Eye, or Smoky Quartz to protect your energy and manifest stable wealth.</p>
        </div>
    
        <div class="element-item metal">
            <h3>⚔️ METAL (The Shield of Clarity)</h3>
            <p><strong>The Energy:</strong> Focus, resilience, and purity. Metal energy cuts through confusion, helping you set strong boundaries and maintain a clear, unwavering mind.</p>
            <p><strong>When you need it:</strong> If you seek to clear mental clutter, let go of the past, or need the inner strength to stand up for yourself.</p>
            <p><strong>Gemstone Match:</strong> Seek out pristine stones like Clear Quartz, White Moonstone, or Gold Rutilated Quartz for unclouded judgment and unshakeable focus.</p>
        </div>
    
        <div class="element-item water">
            <h3>💧 WATER (The Flow of Wisdom)</h3>
            <p><strong>The Energy:</strong> Intuition, adaptability, and emotional depth. Water flows around any obstacle, teaching us how to navigate life's challenges with grace.</p>
            <p><strong>When you need it:</strong> When life feels overwhelming, and you need to protect your inner peace, enhance your intuition, or improve communication.</p>
            <p><strong>Gemstone Match:</strong> Dive into deep tones like Black Obsidian, Lapis Lazuli, or Aquamarine to absorb negative energy and tap into your highest wisdom.</p>
        </div>
    
        <div class="footer-section">
            <p><strong>How to Choose?</strong> You can select a gemstone based on your birth year's element, or simply listen to your intuition. The element you are most drawn to today is often exactly the energy your spirit needs to heal and thrive.</p>
        </div>
    </div>
</div>
@endsection
