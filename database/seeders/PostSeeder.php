<?php

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $baseUrl = rtrim((string) env('APP_URL', ''), '/');

        $posts = [
            [
                'slug' => 'five-elements-gemstone-guide',
                'title' => 'Choosing Gemstones by the Five Elements',
                'excerpt' => 'Wood, fire, earth, metal, and water each resonate with specific stones. Learn how to match color and crystal energy to your feng shui goals.',
                'body' => <<<'HTML'
<p>In classical feng shui, the Five Elements (Wu Xing) describe how energy moves through your life and space. Gemstones carry elemental qualities through color, mineral composition, and the intention you set when you wear them.</p>
<h2>Wood — growth and renewal</h2>
<p>Green stones such as jade and green aventurine support new beginnings, creativity, and emotional flexibility. Wear them when you want to invite fresh projects or soften rigidity in daily habits.</p>
<h2>Fire — visibility and passion</h2>
<p>Red and warm orange agates amplify confidence, recognition, and warmth in relationships. A red Fox Queen Stone bracelet can act as a gentle reminder to speak up and show your work with grace.</p>
<h2>Earth — stability and nourishment</h2>
<p>Yellow and brown tones—citrine, tiger eye, honey agate—ground scattered energy and support digestion of change. They are ideal during transitions at home or at work.</p>
<h2>Metal — clarity and boundaries</h2>
<p>White quartz, moonstone, and clear agate refine focus and help you release what no longer serves. Pair metal-element stones with tidy spaces for a sharper mind.</p>
<h2>Water — wisdom and flow</h2>
<p>Black obsidian and deep blue lapis encourage reflection, protection, and intuitive listening. Water-element jewelry is often worn on the left wrist to receive energy in traditional practice.</p>
<p>Start with one stone that matches your current season of life, then layer complementary colors rather than wearing every element at once. Balance, not overload, is the heart of feng shui.</p>
HTML,
                'image' => $baseUrl.'/storage/products/gallery/heal-balance.webp',
                'sort_order' => 1,
                'published_at' => now()->subDays(14),
            ],
            [
                'slug' => 'pixiu-obsidian-feng-shui-wear',
                'title' => 'Pixiu, Obsidian, and the Art of Wearing Protection Stones',
                'excerpt' => 'Guardian charms and volcanic glass have long been worn for abundance and shielding. Here is a practical guide to direction, wrist, and mindful daily wear.',
                'body' => <<<'HTML'
<p>Pixiu (Pi Yao) is a celestial guardian in Chinese tradition—said to attract wealth while guarding against loss. Obsidian, formed from cooled lava, is valued for its dense, protective field and grounding weight on the wrist.</p>
<h2>Which wrist?</h2>
<p>Many practitioners wear Pixiu bracelets on the left hand to draw in supportive energy, and protective black stones on the right to project boundaries outward. If you are new to either custom, choose the wrist that feels natural and stay consistent for at least a lunar month.</p>
<h2>Respect the guardian</h2>
<p>Pixiu is often depicted with an open mouth and no rear—symbolizing wealth that enters but does not leave. Avoid letting others touch your charm casually, and remove it before sleep or intimate activities if you follow classical etiquette.</p>
<h2>Pairing with intention</h2>
<p>Before your first wear, hold the bracelet and name one clear intention: protection on your commute, steadiness in negotiations, or gratitude for what you already have. Feng shui works best when paired with ethical action, not passive waiting.</p>
<h2>Cleansing obsidian</h2>
<p>Rinse briefly under cool water, pat dry, and rest the piece on a clean cloth overnight. Smudging with sage is optional; avoid harsh chemicals that dull the polish of companion metal beads.</p>
<p>Whether you choose green obsidian with Pixiu or a simpler beaded strand, let the piece remind you to move through the day with awareness—not fear.</p>
HTML,
                'image' => $baseUrl.'/storage/products/gallery/xanh1.png',
                'sort_order' => 2,
                'published_at' => now()->subDays(7),
            ],
            [
                'slug' => 'cleansing-charging-spiritual-jewelry',
                'title' => 'Cleansing and Charging Your Spiritual Jewelry',
                'excerpt' => 'Sunlight, moonlight, sound, and salt are simple rituals to refresh gemstone bracelets and pendants between wears.',
                'body' => <<<'HTML'
<p>Gemstones absorb the moods and environments we move through. Periodic cleansing keeps your jewelry feeling light and aligned with your current intention.</p>
<h2>Gentle methods for most stones</h2>
<p>Wipe beads with a soft damp cloth, then air-dry. For hard stones like agate and quartz, a few hours of indirect morning sun can brighten energy; avoid prolonged hot sun on dyed or treated strands.</p>
<h2>Moonlight charging</h2>
<p>Place your bracelet on a windowsill during the full moon or the night before an important meeting. Moonlight is especially favored for jade, pearl, and pale stones associated with calm insight.</p>
<h2>Sound and breath</h2>
<p>A singing bowl, bell, or three conscious breaths over the piece can reset mental clutter without touching water—useful for mixed-metal designs or silk cords.</p>
<h2>What to avoid</h2>
<p>Do not soak porous stones, use ultrasonic cleaners on fragile inclusions, or mix household bleach with metal accents. Harsh baths can weaken cord and pit softer minerals.</p>
<h2>Setting a new intention</h2>
<p>After cleansing, hold the jewelry at heart level and state one sentence you want to embody this week. Wear it daily for a short period so the ritual becomes habit, not performance.</p>
<p>Thoughtful care extends the life of your piece and deepens your relationship with the stone—true feng shui is maintenance of both object and self.</p>
HTML,
                'image' => $baseUrl.'/storage/products/gallery/lucky-charms.webp',
                'sort_order' => 3,
                'published_at' => now()->subDay(),
            ],
        ];

        foreach ($posts as $row) {
            Post::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'title' => $row['title'],
                    'excerpt' => $row['excerpt'],
                    'body' => $row['body'],
                    'image' => $row['image'],
                    'is_active' => true,
                    'published_at' => $row['published_at'],
                    'sort_order' => $row['sort_order'],
                ]
            );
        }
    }
}
