import {
    getTargetAcheivedBgColor,
    getTargetAcheivedColor,
} from "@/Constant/constants";
import { Chip as MuiChip, styled } from "@mui/material";
import { green } from "@mui/material/colors";

export default function TargetsTableCell({ row, column }) {
    switch (column.id) {
        case "name":
            return row.name;
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
            const percentage = (row.target_achieved * 100) / row.target_value;

            return (
                row.target_achieved >= 0 && (
                    <TargetAcheivedChip
                        clickable
                        size="small"
                        label={row.target_achieved}
                        percentage={percentage}
                        sx={{ borderRadius: 1 }}
                    />
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

// get color according to the target acheived (if target acheived is 100% then green else red)
const TargetAcheivedChip = styled(MuiChip)`
    font-weight: 600;
    background-color: ${({ percentage }) =>
        getTargetAcheivedBgColor(percentage)};
    color: ${({ percentage }) => getTargetAcheivedColor(percentage)};
    &:hover {
        background-color: ${({ percentage }) =>
            getTargetAcheivedBgColor(percentage)};
        color: ${({ percentage }) => getTargetAcheivedColor(percentage)};
    }
    &:active {
        background-color: ${({ percentage }) =>
            getTargetAcheivedBgColor(percentage)};
        color: ${({ percentage }) => getTargetAcheivedColor(percentage)};
    }
    &:focus {
        background-color: ${({ percentage }) =>
            getTargetAcheivedBgColor(percentage)};
        color: ${({ percentage }) => getTargetAcheivedColor(percentage)};
    }
`;
