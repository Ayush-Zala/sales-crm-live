import {
    getTargetAcheivedBgColor,
    getTargetAcheivedColor,
} from "@/Constant/constants";
import { Chip as MuiChip, styled, Box, LinearProgress, Typography } from "@mui/material";
import { green } from "@mui/material/colors";

export default function TargetsTableCell({ row, column }) {
    switch (column.id) {
        case "name":
            return <Typography fontWeight={500}>{row.name}</Typography>;
        case "target_value":
            return (
                row.target_value >= 0 && (
                    <TargetValueChip
                        clickable
                        size="small"
                        label={row.target_value}
                        sx={{ borderRadius: 1 }}
                    />
                )
            );
        case "target_achieved":
            const rawPercentage = row.target_value > 0 ? (row.target_achieved * 100) / row.target_value : 0;
            const percentage = Math.min(rawPercentage, 100);

            return (
                row.target_achieved >= 0 && (
                    <Box sx={{ width: '100%', mr: 1, minWidth: 100 }}>
                        <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 0.5 }}>
                            <Typography variant="body2" color="text.secondary">
                                {row.target_achieved}
                            </Typography>
                            <Typography variant="body2" color="text.secondary" fontWeight="bold">
                                {Math.round(percentage)}%
                            </Typography>
                        </Box>
                        <LinearProgress 
                            variant="determinate" 
                            value={percentage} 
                            sx={{ 
                                height: 8, 
                                borderRadius: 4,
                                backgroundColor: '#e0e0e0',
                                '& .MuiLinearProgress-bar': {
                                    backgroundColor: percentage >= 100 ? '#4caf50' : '#1976d2'
                                }
                            }}
                        />
                    </Box>
                )
            );
        case "time":
            return row.time;
        default:
            return null;
    }
}

const TargetValueChip = styled(MuiChip)`
    font-weight: 600;
    background-color: ${green[100]};
    color: ${green[800]};
    &:hover {
        background-color: ${green[100]};
        color: ${green[800]};
    }
    &:active {
        background-color: ${green[100]};
        color: ${green[800]};
    }
    &:focus {
        background-color: ${green[100]};
        color: ${green[800]};
    }
`;
