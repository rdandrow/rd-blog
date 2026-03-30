type ChartDataset = {
    label: string;
    data: number[];
    borderColor: string;
    backgroundColor: string;
    pointRadius: number;
    pointHoverRadius: number;
    borderWidth: number;
    tension: number;
    fill: boolean;
};

const chartFallbackColor = '#94a3b8';

const normalizeTokenColor = (tokenValue: string): string => {
    if (!tokenValue) {
        return chartFallbackColor;
    }

    const normalized = tokenValue.trim();

    if (normalized.includes('(') || normalized.startsWith('#')) {
        return normalized;
    }

    return `hsl(${normalized})`;
};

const withAlpha = (color: string, alpha: number): string => {
    const clampedAlpha = Math.max(0, Math.min(1, alpha));
    const percent = Math.round(clampedAlpha * 100);

    return `color-mix(in srgb, ${color} ${percent}%, transparent)`;
};

export const resolveChartColor = (token: string, alpha?: number): string => {
    if (typeof window === 'undefined') {
        return alpha === undefined ? chartFallbackColor : withAlpha(chartFallbackColor, alpha);
    }

    const tokenValue = getComputedStyle(document.documentElement)
        .getPropertyValue(token)
        .trim();

    const color = normalizeTokenColor(tokenValue);

    return alpha === undefined ? color : withAlpha(color, alpha);
};

export const buildLineDataset = (
    label: string,
    values: number[],
    colorToken: string,
    fill = false,
): ChartDataset => ({
    label,
    data: values,
    borderColor: resolveChartColor(colorToken),
    backgroundColor: resolveChartColor(colorToken, 0.2),
    pointRadius: 0,
    pointHoverRadius: 3,
    borderWidth: 2,
    tension: 0.3,
    fill,
});

export const buildChartData = (
    labels: string[],
    datasets: ChartDataset[],
    themeVersion: number,
) => ({
    _themeVersion: themeVersion,
    labels,
    datasets,
});

export const buildTrendOptions = (showLegend: boolean, themeVersion: number) => ({
    _themeVersion: themeVersion,
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        mode: 'index' as const,
        intersect: false,
    },
    plugins: {
        legend: showLegend
            ? {
                  position: 'bottom' as const,
                  labels: {
                      color: resolveChartColor('--foreground'),
                      boxWidth: 10,
                      boxHeight: 10,
                      usePointStyle: true,
                      pointStyle: 'line' as const,
                  },
              }
            : {
                  display: false,
              },
    },
    scales: {
        x: {
            ticks: {
                color: resolveChartColor('--muted-foreground'),
                maxTicksLimit: 6,
            },
            grid: {
                color: resolveChartColor('--border', 0.25),
            },
        },
        y: {
            beginAtZero: true,
            ticks: {
                color: resolveChartColor('--muted-foreground'),
                precision: 0,
            },
            grid: {
                color: resolveChartColor('--border', 0.45),
            },
        },
    },
});