// Define Tailwind configuration once for the entire site
tailwind.config = {
    darkMode: 'class',
    theme: {
        extend: {
            colors: {
                'theme-primary': 'var(--theme-primary)',
                'theme-primary-hover': 'var(--dashboard-primary-hover)',
                'theme-secondary': 'var(--theme-secondary)',
                'dashboard-bg': 'var(--dashboard-bg)',
                'dashboard-card-bg': 'var(--dashboard-card-bg)',
                'dashboard-text': 'var(--dashboard-text)',
                'dashboard-text-secondary': 'var(--dashboard-text-secondary)',
                'dashboard-border': 'var(--dashboard-border)',
                'dashboard-success': 'var(--dashboard-success)',
                'dashboard-danger': 'var(--dashboard-danger)',
                'dashboard-warning': 'var(--dashboard-warning)',
                'dashboard-info': 'var(--dashboard-info)',
                // Add primary color range that uses the site theme
                primary: {
                    100: 'var(--primary-100, #dbeafe)',
                    200: 'var(--primary-200, #bfdbfe)',
                    300: 'var(--primary-300, #93c5fd)',
                    400: 'var(--primary-400, #60a5fa)',
                    500: 'var(--theme-primary)',
                    600: 'var(--theme-primary)',
                    700: 'var(--theme-secondary)',
                    800: 'var(--theme-secondary)',
                    900: 'var(--dashboard-primary-hover)',
                    950: 'var(--dashboard-primary-hover)',
                }
            }
        },
    }
};