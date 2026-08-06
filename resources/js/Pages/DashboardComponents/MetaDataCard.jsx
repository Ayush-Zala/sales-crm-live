import React from 'react';
import { Card, CardContent, Stack, Typography, Box } from "@mui/material";
import { SparkLineChart } from '@mui/x-charts/SparkLineChart';

const MetaDataCard = ({
    format,
    title,
    count,
    handleClickOnCount,
    extraCount,
    isFilterAllowed,
    isLinkOnCount,
    FilterComponent,
    filter,
    setFilter,
    trend,
    icon,
    iconBg = '#e3f2fd',
    sparklineData = null,
    trendLabel,
}) => {
    const formatedCount = format
        ? new Intl.NumberFormat("en-IN").format(count)
        : count;

    const isPositive = (trend || 0) >= 0;
    const trendColor = isPositive ? 'success.main' : 'error.main';
    const trendIcon = isPositive ? '↑' : '↓';

    return (
        <Card sx={{ 
            borderRadius: 4, 
            boxShadow: '0 4px 20px rgba(0,0,0,0.05)',
            border: '1px solid #f0f0f0',
            position: 'relative',
            height: '100%'
        }}>
            <CardContent sx={{ p: 3, "&:last-child": { pb: 3 }, display: 'flex', flexDirection: 'column', height: '100%', justifyContent: 'space-between' }}>
                <Box>
                    {isFilterAllowed && FilterComponent && (
                        <Box sx={{ position: 'absolute', top: 12, right: 12 }}>
                            <FilterComponent filter={filter} setFilter={setFilter} />
                        </Box>
                    )}
                    
                    <Stack direction="row" spacing={2} alignItems="center" mb={2}>
                        {icon && (
                            <Box sx={{ 
                                bgcolor: iconBg, 
                                p: 1.5, 
                                borderRadius: 3,
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center'
                            }}>
                                {icon}
                            </Box>
                        )}
                        <Box>
                            <Typography color="text.secondary" fontWeight="600" variant="body2">
                                {title}
                            </Typography>
                            <Typography 
                                variant="h5" 
                                fontWeight="bold"
                                onClick={handleClickOnCount}
                                sx={{
                                    cursor: isLinkOnCount ? "pointer" : "default",
                                    ":hover": isLinkOnCount ? { color: "primary.main" } : {},
                                }}
                            >
                                {`${formatedCount} ${extraCount ? "/ " + extraCount : ""}`}
                            </Typography>
                        </Box>
                    </Stack>
                </Box>

                <Stack direction="row" alignItems="flex-end" justifyContent="space-between">
                    {trend !== undefined ? (
                        <Box>
                            <Typography variant="body2" sx={{ color: trendColor, fontWeight: 'bold' }}>
                                {trendIcon} {Math.abs(trend)}%
                            </Typography>
                            <Typography variant="caption" color="text.secondary">
                                {trendLabel || 'vs previous'}
                            </Typography>
                        </Box>
                    ) : (
                        <Box></Box> /* Empty box to push sparkline to right if needed */
                    )}
                    
                    {sparklineData && sparklineData.length > 1 && (
                        <Box sx={{ width: 100, height: 40 }}>
                            <SparkLineChart 
                                data={sparklineData} 
                                colors={[isPositive ? '#4caf50' : '#f44336']} 
                                curve="natural"
                                showTooltip
                            />
                        </Box>
                    )}
                </Stack>
            </CardContent>
        </Card>
    );
};

export default MetaDataCard;
