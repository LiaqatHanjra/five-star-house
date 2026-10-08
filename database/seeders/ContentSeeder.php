<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\SiteImage;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $images = [
            [
                'page' => 'home',
                'slot' => 'hero',
                'title' => 'STAGE A • CAMERA READY',
                'category' => 'REF • 001/MTL',
                'alt' => 'Director portrait',
                'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuC1-ERtUSDVtTPviEpWtqV5a9niiItEi3R8ME3X4LJpv7kSHKQTciqGl9pDWjEFE134CIC4k_88rWOWR_FQNNZcNeoXqjS1ATfmrsj0IecRnopDcDC5K_ilq8K_9cYs_01AXt-s2tQgYhSgUBFfpjGT2NV_U4Vhi0RQfbeSsFq7K0mcDENYhuEUDuQyn6FiJxnTSUc9elhqWL7f8RKhlb0t6yGlLE-Tww1iv-EqSn1w02S7SHWjWhrr',
                'sort_order' => 0,
            ],
            [
                'page' => 'home',
                'slot' => 'gallery',
                'title' => 'AURA EDITORIAL',
                'category' => 'CAMPAIGN',
                'alt' => 'Editorial Portrait',
                'col_class' => 'col-md-3',
                'aspect' => 'aspect-4x5',
                'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuA4pNGAC4zw9WJVOFeZc5S82llmMrvlxvumDYz8QmXpZTuOJ3L8Faf---LJsK98K8zwR7xPkF0VtTV04UDiEKoHhe3ZRWcGuKJNMjdqO8aOAO4Trh0dpe6P0fDZas74J4VRYom0xWOiVWdlGYiSZ5kO5WC66rEvfqV-7GCDxPiNXv9aDepOim8cSb_LGx3_6gvIupNecapGZ66WUTHZ8VWtyjQQqJJabT3OpbqG8pEfpali-VCogkIK',
                'sort_order' => 1,
            ],
            [
                'page' => 'home',
                'slot' => 'gallery',
                'title' => 'HORIZON PROTOCOL',
                'category' => 'COMMERCIAL',
                'alt' => 'Skyline Studio Set',
                'col_class' => 'col-md-5',
                'aspect' => 'aspect-square aspect-md-auto',
                'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDUHkOK8D3bB76I7Eko6M0EgCBgPWEwiRCZGD21Ap4ZOOE-qWiiQM1NNMwJxLqFmEm7J9RaB4YNzzDWrVUhSG6OCQURGh4XAXgI_rlTyCMiP13PlDG_NbOyxHdsT-la3RPI6H9r-NIHQVzW8XoMee5oBxDlKzClehugh31yZ6eMBRy-5_6br9tApPCYnhxg4rXYzLsx7zibT3OcE1mIyugUW_H6GGNI3nxrYf_g3YLP4TlkMDa5e4j9',
                'sort_order' => 2,
            ],
            [
                'page' => 'home',
                'slot' => 'gallery',
                'title' => 'SONIC SESSION 04',
                'category' => 'DOCUMENTARY',
                'alt' => 'Live Music Performance',
                'col_class' => 'col-md-4',
                'aspect' => 'aspect-4x5',
                'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuA9SfB7sjZ7CoB0dPDR7CIUZ4ckgu0H7tTHjIoyt_SDz3KzTwoEhoiWx1GDO_AHJfeC4I_wa1Ht3ODxgc-5G6WRH3VOLS-d0e1WHLkrmS19pvRwtrPm9A7IJa_RZodEljaSiNMxF4E1o7mv1ZJlj9XWN9c9KdGDPAYAgq8teD3Tl-FRKAvGowYngdW2sfcwG9YF_45_Eg-M2z1TzhrREWijDXE0yN3iprPqT3M0MX8x40gxqNaLzWee',
                'sort_order' => 3,
            ],
            [
                'page' => 'home',
                'slot' => 'gallery',
                'title' => 'SCARLET RUNNER',
                'category' => 'HAUTE COUTURE',
                'alt' => 'Couture Red Dress',
                'col_class' => 'col-md-4',
                'aspect' => 'aspect-4x5',
                'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuB_dwwp7exPcwy5DHgXm1iX7abu7uQFy9usmXlaIgIJ0f4jNRr8LEh-HV0-kCO23mhDyEmBFuKLMzrxSXuUGBfsc8STcxrQeXxCKBwyQaSjlwUnkCbXM9boLihCr5zlEKdCPhTTIkeyiJ43V_1oXhLqz9bNMs7_EVdDqDr6FteeNFvZS-XXiygQIV3t6PVzMwHy6of6I2TAe0x0rCDe0L24yWmqMukBqMnTTfWTJSZyBXQWAoPjJKp9',
                'sort_order' => 4,
            ],
            [
                'page' => 'home',
                'slot' => 'gallery',
                'title' => 'AFTERLIGHT FEATURE',
                'category' => 'NARRATIVE FILM',
                'alt' => 'Cinema Camera Rig',
                'col_class' => 'col-md-8',
                'aspect' => 'aspect-video aspect-md-auto',
                'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCoOXVLuRcQOxP7QZOYPq35kJtzQeW5H_RQnX3V5S0D6ISZK0ixWToU7PCFgzlxLfpVMKWvLnrkDTyheYIfc-k_BuDYYcysC-3c6e6F2aDpD0G9dGD1xk8O-_qz3C05XNtQ8wXXqsqaYBrG_R1hWBz09DOcDz0WhF0Fwd3ijxhnCQ-96MyBKXBZUVAu8ftu_xUoDZM_km0QeMfFPZAK1R1faj3vD-mtLSHPT9tqrJmEtM-rbL7IKAR6',
                'sort_order' => 5,
            ],
            [
                'page' => 'home',
                'slot' => 'gallery',
                'title' => 'STAGE A • 1,600 SQ FT CYC',
                'category' => 'FACILITY',
                'alt' => 'White Cyclorama Studio',
                'col_class' => 'col-md-7',
                'aspect' => 'aspect-video',
                'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBe1C0sytJGs3amJjQ7YlnQjEvRndS-5M2Xc5OaYGqdSFX4GCgUx5Xj_SCjGNNPCweQtFJhvk9SuWd_3shxDF8YHUdjIDi9vUa-zjEhHNOkU1KSme_Qi6s_aanP5vaX3wEWIFt_-PFR-T---jyid2whicd1AKIZC3GXNIvJQDodiSsMf6Hvg2gc2CJzJCX7Tmv9GGLQPe3ymm1xDU3L5B4_a0Mcv0YbrwYi703PcoomurOKxqMmu76O',
                'sort_order' => 6,
            ],
            [
                'page' => 'home',
                'slot' => 'gallery',
                'title' => 'COLOR & FINISHING',
                'category' => 'POST PRODUCTION',
                'alt' => 'Color Grading Suite',
                'col_class' => 'col-md-5',
                'aspect' => 'aspect-video',
                'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuAaAjjd52OvGtIuP0mz1zZHypLs-zmiVod4Fj30JUGsJLLukuvRcAK5fjapuoS63v1rVp5mYPaZzKG1K8o7xI-wCNeZWPMzGmj2-no3xAndhkE8XUL1mfyDyMMI56EZVFk6q5wYbr5SUbuLOgRE37M2b6nn59-_rGkvF8PUskFHai0KNn2O_e0KTuxpSBObZSdePFAT1ok1aWThWqamGaZea0tOwOO2Y_HxmeBhFXl_smw55gGfc7xW',
                'sort_order' => 7,
            ],
            [
                'page' => 'services',
                'slot' => 'hero',
                'title' => 'MONTREAL, QC',
                'category' => 'STAGE 01 • LIVE CAPTURE',
                'alt' => 'Live studio capture',
                'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBb8ZyHchWv6CjmVdIDU1OxNubd64Avgg31BMlrg66utJwGToe98i_piWsYi3XPpBUAN09imlf2AuuBGM_hI9zHDcrPr9JPV9CyNBf9x2WARoyAX2YIIYifNLVNhwy8tYhEU9CjmApb7bqJemxrgz3yWCwt7U-KMD5hPzT9CbarF3z4QG1O161sTUuiIe_7fbAoACqX3yZpwX9e40u8BlIoRQiYbgmnEO0ZOqGQvGJgEgp5z74sgj52',
                'sort_order' => 0,
            ],
            [
                'page' => 'booking',
                'slot' => 'sidebar',
                'title' => 'STAGE A • 1,600 SQ FT',
                'alt' => 'Stage A cyclorama',
                'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDejsjaKX4SU_sGaMPJ_at0Opq3Qkq1LtzBIOKWOAPKEsXLmKSzXnLyhsbawmRCkL-zIw8dchAuuJemW4zmTOZFlDd1pJFgv0sACmGFOVrvTVTN6y8L4tRpdWJ1VwR49YYkq38lFzavIPe7BJvlPbdtEABB49ENj80XOT_ii49sO5SmYRhN7Lyav6qajWPFPzNp4ue28zz9R6N_SjSVJv3ej-qN48XDFBmaaoWF98z2y_2JlusmDE1m',
                'sort_order' => 0,
            ],
            [
                'page' => 'booking',
                'slot' => 'banner',
                'title' => 'MILE-EX • MONTREAL',
                'category' => 'STUDIO DISTRICT',
                'caption' => "Located within the city's foremost creative and technological nexus, engineered for seamless production transit and loading access.",
                'alt' => 'Montreal studio district',
                'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuAILpiJEu_j6NrK9BOayuWw4H9y49RUdHC_3UoK4rObGHDwzlidXX5SDMTFUk7fwEpVb78iB5DAeNgX1N7xQD5l0Ryi7NNIA2YUcX9EK2GWbCVrmemve6i4-Q-ghwoWBenrYGXKfIRXV7iSe-IqSRX8jYUvtdMFa_4fZJJcQzP6VRua_5Ys6namouCDsoT1Lu3bvUKJJ0NE7Tq5HeiPNhRMzIdmS5yxLuWgtIOLUMRsZsgaDpGl7baH',
                'sort_order' => 1,
            ],
        ];

        foreach ($images as $image) {
            SiteImage::create($image + ['is_active' => true]);
        }

        $services = [
            [
                'number' => '01',
                'title' => 'FILM PRODUCTION',
                'slug' => 'film-production',
                'summary' => 'Commercials, branded documentary, narrative films, and music videos. Seamless turnkey execution from treatment to master delivery.',
                'description' => 'Commercials, branded content, documentaries, music videos, and cinematic promos. We transform abstract narrative briefs into visually commanding stories utilizing cutting-edge ARRI Alexa Mini LF and RED V-Raptor cinema packages paired with vintage Cooke and Atlas anamorphic optics.',
                'eyebrow' => 'COMMERCIAL • NARRATIVE',
                'badge' => 'CINEMA RIG // 8K',
                'charge' => 8500,
                'charge_unit' => 'project',
                'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuB8nGKF9F777lgmUJ3R7ri40jsH6429V6WsveAvgqEHZN1ITLhjCK5zx3eMLEmQDG0_oNIeTg8oT_2E5TaoBi4k-gSgvHVpNgttZbD2mUYjznVcPvgHTc3XEZjLXOJh-rN6xxb2DaS0-A6U8j56v_rHUMgmPGnaJBUTF-CFYFpr-62Neb2wY4ywzqFB0YrfNhtEybNoafDoHOf_EVo8QsEcOm8LLqQqbRw8clQ__hg8crJkRRDTXxzY',
                'tags' => ['Concept Development', 'Scriptwriting & Storyboarding', 'Cinematography', 'Directing & Staging', 'DaVinci Color & Sound Design'],
                'cta_label' => 'REQUEST QUOTE',
                'image_side' => 'left',
                'sort_order' => 1,
            ],
            [
                'number' => '02',
                'title' => 'PHOTOGRAPHY',
                'slug' => 'photography',
                'summary' => 'Editorial portraiture, campaign lookbooks, product stills, and high-impact lifestyle captures tailored for global broadcast.',
                'description' => 'Portraits, high-fashion editorials, global advertising, product still lifes, and dynamic brand campaigns. Clean, impactful images engineered for luxury print publications, multi-channel digital rollouts, and billboard executions with uncompromising color precision.',
                'eyebrow' => 'STILL CAMPAIGNS • EDITORIAL',
                'badge' => 'STILL // MEDIUM FORMAT',
                'charge' => 2500,
                'charge_unit' => 'project',
                'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCEpdNb55TDgM1GXVneUEjEMI5XIzReBUyHwEV4nR5fJW8ehcCXRDCp9tYzKHhxyOfaRamlZoia9fJV5H3MXaoeqaIBGQlLuPsL62RQmv75mCBhNx_8igG_N8phRu1vZ8QB7_sA_5FOuFWOijDpbp6dTXYJ9qf3-jTaJjgTVn8IyljUWmmM-QmAng03mbnesOjhT7SzBwwkxm4YijBrXzyOiF4QTTA47n2DBvAE8jGNyI76KrxTvYXX',
                'tags' => ['Fashion & Lookbooks', 'Commercial Advertising', 'Product & E-Commerce', 'Executive & Talent Portraits', 'Architecture & Spatial Capture'],
                'cta_label' => 'REQUEST QUOTE',
                'image_side' => 'right',
                'sort_order' => 2,
            ],
            [
                'number' => '03',
                'title' => 'CREATIVE DIRECTION',
                'slug' => 'creative-direction',
                'summary' => 'Conceptual frameworks, style guides, set design styling, script doctoring, and overarching visual identity systems.',
                'description' => 'We shape vision with strategic creative direction from inception to master cut. Aligning visual storytelling with brand codes, we generate bespoke visual treatments, curate production teams, oversee art direction, and engineer compelling cultural relevance.',
                'eyebrow' => 'STRATEGY • CONCEPT ARCHITECTURE',
                'badge' => 'CURATION // IDENTITY',
                'charge' => 3500,
                'charge_unit' => 'project',
                'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBYLXFVy5xB7g0J-441an2gFBqm2r6xU0oIfM0nUPijBHikYEUADEZuFcTs6_0TcrWCeqw3j1Nwwmesb2EaWwg_qUcJWpleA3e3y5Sohbj9v7wTtvLpccxNVgF2q1SXFPGDTclyl_GUw4-wgQWFo7K8jVy9H3a8iXSR8iOaK6McrgXn1qcuzy070Y-CDXQBA2O6-z7wlKU2noWJ6R5NOU1wI2QLbgWRsZchZeNWF9aeY_JQDhOrGNaK',
                'tags' => ['Brand Visual Identity', 'Art Direction', 'Moodboards & Director Treatments', 'Production Design', 'Campaign Strategy & Worldbuilding'],
                'cta_label' => 'REQUEST QUOTE',
                'image_side' => 'left',
                'sort_order' => 3,
            ],
            [
                'number' => '04',
                'title' => 'STUDIO PRODUCTION & CYCLORAMA RENTAL',
                'slug' => 'studio-rental',
                'summary' => 'Dedicated 1,600 sq ft facility in Mile-Ex with corner cyclorama, 3-phase power, HMIs, gear rental package, and client lounge.',
                'description' => "Located in Montreal's creative district, our 1600 sq ft production house offers a pristine corner seamless cyclorama, high industrial ceilings, 3-phase power distribution, and complete client comfort facilities suitable for automotive, editorial, or commercial sound shoots.",
                'eyebrow' => 'PHYSICAL SPACE • SOUNDSTAGE',
                'badge' => 'MILE-EX // 1600 SQ FT',
                'charge' => 1200,
                'charge_unit' => 'day',
                'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuAGuHCYsJ4w5WB1jZ7H0crqUCsMtGYRxwRucYDdjHl8UzhYMs-LNDRIW8kYAJ0nw1tH16rfk_bkQEbeIsFzxs1Xf9KyvBLwp_CV-Km8zIcC_us2uBMtR21v2J30RqKWKSn53outtHgcQTmLIpwCg9DOBgJ_M997j_TZs6co0o_IC0GMI1orcOiWMBHXPE85IyqwIGyL0k9SjQyWwWq_S6FIcOMvisjKzEGLrosOVJnTOXbuNbhbQxJl',
                'tags' => ['20ft Corner Cyclorama', '14ft Clear Ceilings', 'Drive-In Equipment Access', 'Client Lounge & Green Room', 'Complete In-House Grip & Aputure Package'],
                'cta_label' => 'BOOK STUDIO TIME',
                'image_side' => 'right',
                'sort_order' => 4,
            ],
        ];

        foreach ($services as $service) {
            Service::create($service + [
                'currency' => 'CAD',
                'is_active' => true,
            ]);
        }
    }
}
