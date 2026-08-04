import { Button } from "@mui/material";
import { exportToExcel } from "react-json-to-excel";

const ExportToExcelComponent = ({ data, fileName }) => {
    const handleClick = () => {
        exportToExcel(data, fileName);
    };

    return (
        <Button variant="contained" color="success" onClick={handleClick}>
            Export to Excel
        </Button>
    );
};

export default ExportToExcelComponent;
