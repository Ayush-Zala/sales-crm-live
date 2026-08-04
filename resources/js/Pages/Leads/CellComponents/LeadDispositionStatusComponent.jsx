import { getBgColor, getColor } from "@/Constant/constants";
import { Chip as MuiChip, styled } from "@mui/material";

const LeadDispositionStatusComponent = ({ label }) => {
    return (
        <Chip clickable size="small" label={label} sx={{ borderRadius: 1 }} />
    );
};

export default LeadDispositionStatusComponent;

const Chip = styled(MuiChip)`
    font-weight: 600;
    background-color: ${({ label }) => getBgColor(label)};
    color: ${({ label }) => getColor(label)};
    &:hover {
        background-color: ${({ label }) => getColor(label)};
        color: ${({ label }) => getBgColor(label)};
    }
    &:active {
        background-color: ${({ label }) => getColor(label)};
        color: ${({ label }) => getBgColor(label)};
    }
    &:focus {
        background-color: ${({ label }) => getColor(label)};
        color: ${({ label }) => getBgColor(label)};
    }
`;
