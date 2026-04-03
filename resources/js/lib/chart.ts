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
    const a = Math.max(0, Math.min(1, alpha));

    // hsl(...) — handles both space-separated (modern) and comma-separated syntax
    const hslMatch = color.match(/^hsl\(\s*([\d.]+)[,\s]+([\d.]+%?)[,\s]+([\d.]+%?)/);
    if (hslMatch) {
        return `hsla(${hslMatch[1]}, ${hslMatch[2]}, ${hslMatch[3]}, ${a})`;
    }

    // #rrggbb or #rgb
    if (color.startsWith('#')) {
        const hex = color.length === 4
            ? [color[1] + color[1], color[2] + color[2], color[3] + color[3]]
            : [color.slice(1, 3), color.slice(3, 5), color.slice(5, 7)];
        const [r, g, b] = hex.map((h) => parseInt(h, 16));
        return `rgba(${r}, ${g}, ${b}, ${a})`;
    }

    return color;
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

type BarDataset = {
    label: string;
    data: number[];
    backgroundColor: string;
    borderColor: string;
    borderWidth: number;
    borderRadius: number;
};

export const buildBarDataset = (
    label: string,
    values: number[],
    colorToken: string,
): BarDataset => ({
    label,
    data: values,
    backgroundColor: resolveChartColor(colorToken, 0.8),
    borderColor: resolveChartColor(colorToken),
    borderWidth: 0,
    borderRadius: 3,
});

export const buildBarChartData = (
    labels: string[],
    datasets: BarDataset[],
    themeVersion: number,
) => ({
    _themeVersion: themeVersion,
    labels,
    datasets,
});

export const buildBarOptions = (showLegend: boolean, themeVersion: number) => ({
    _themeVersion: themeVersion,
    responsive: true,
    maintainAspectRatio: false,
    indexAxis: 'y' as const,
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
                      pointStyle: 'rect' as const,
                  },
              }
            : {
                  display: false,
              },
    },
    scales: {
        x: {
            beginAtZero: true,
            ticks: {
                color: resolveChartColor('--muted-foreground'),
                precision: 0,
            },
            grid: {
                color: resolveChartColor('--border', 0.45),
            },
        },
        y: {
            ticks: {
                color: resolveChartColor('--muted-foreground'),
            },
            grid: {
                display: false,
            },
        },
    },
});