<?php
/**
 * ============================================
 * Theme Configuration
 * ============================================
 * 
 * Centralized theme and styling configuration
 * Professional theme management
 */

class ThemeConfig {
    
    /**
     * Primary Colors
     */
    public static function getColorScheme() {
        return [
            'primary' => '#0f8bff',
            'primary_alt' => '#82b7ff',
            'bg_primary' => '#0d0d0d',
            'bg_secondary' => '#1a1a1a',
            'text_primary' => '#e0e0e0',
            'text_muted' => '#a0a0a0',
            'border' => '#333333',
            'success' => '#51cf66',
            'danger' => '#ff6b6b',
            'warning' => '#ffd43b',
            'info' => '#4dabf7',
        ];
    }

    /**
     * Get specific color
     * 
     * @param string $colorKey Color key name
     * @return string Color hex value
     */
    public static function getColor($colorKey) {
        $colors = self::getColorScheme();
        return $colors[$colorKey] ?? '#000000';
    }

    /**
     * Typography Settings
     */
    public static function getTypography() {
        return [
            'font_primary' => "'Lato', sans-serif",
            'font_size_base' => '16px',
            'font_size_sm' => '14px',
            'font_size_lg' => '18px',
            'font_size_h1' => '2.5rem',
            'font_size_h2' => '2rem',
            'font_size_h3' => '1.75rem',
            'font_size_h4' => '1.5rem',
            'font_size_h5' => '1.25rem',
            'font_weight_normal' => '400',
            'font_weight_medium' => '500',
            'font_weight_bold' => '700',
            'font_weight_heavy' => '900',
            'line_height_sm' => '1.4',
            'line_height_base' => '1.6',
            'line_height_lg' => '1.8',
        ];
    }

    /**
     * Spacing & Sizing
     */
    public static function getSpacing() {
        return [
            'xs' => '0.25rem',
            'sm' => '0.5rem',
            'md' => '1rem',
            'lg' => '1.5rem',
            'xl' => '2rem',
            'xxl' => '3rem',
        ];
    }

    /**
     * Border Radius
     */
    public static function getBorderRadius() {
        return [
            'sm' => '0.25rem',
            'base' => '0.35rem',
            'md' => '0.5rem',
            'lg' => '1rem',
            'xl' => '1.5rem',
            'round' => '9999px',
        ];
    }

    /**
     * Shadow Effects
     */
    public static function getShadows() {
        return [
            'sm' => '0 1px 4px rgba(0, 0, 0, 0.4)',
            'md' => '0 2px 8px rgba(0, 0, 0, 0.5)',
            'lg' => '0 4px 12px rgba(0, 0, 0, 0.6)',
            'xl' => '0 8px 16px rgba(0, 0, 0, 0.7)',
        ];
    }

    /**
     * Breakpoints - Responsive Design
     */
    public static function getBreakpoints() {
        return [
            'xs' => '0px',
            'sm' => '576px',
            'md' => '768px',
            'lg' => '992px',
            'xl' => '1200px',
            'xxl' => '1400px',
        ];
    }

    /**
     * Navigation Configuration
     */
    public static function getNavigationMenu() {
        return [
            [
                'label' => 'About Center',
                'href' => 'about.php',
                'icon' => 'info-circle',
                'auth' => false,
            ],
            [
                'label' => 'Contact',
                'href' => 'contact.php',
                'icon' => 'envelope',
                'auth' => false,
            ],
        ];
    }

    /**
     * Get CSS variables snippet
     * 
     * @return string CSS code
     */
    public static function getCSSVariables() {
        $colors = self::getColorScheme();
        $css = ":root {\n";
        
        foreach ($colors as $key => $value) {
            $varName = str_replace('_', '-', $key);
            $css .= "  --{$varName}: {$value};\n";
        }
        
        $css .= "}\n";
        return $css;
    }
}

?>
