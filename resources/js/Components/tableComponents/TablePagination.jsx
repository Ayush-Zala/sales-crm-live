import useUpdateSearchParam from "@/hooks/use-update-search-params";
import { TablePagination } from "@mui/material";

const TablePaginationComponent = ({ total, per_page, current_page, url }) => {
    const handlePageChange = (key, value) =>
        useUpdateSearchParam(value ? { [key]: value } : { [key]: null }, url);

    const handleRowsPerPageChange = (key, value) =>
        useUpdateSearchParam(
            value ? { [key]: value, page: 1 } : { [key]: null },
            url
        );

    return (
        <TablePagination
            rowsPerPageOptions={[10, 25, 50, 100, 500]}
            component="div"
            count={total || 0}
            rowsPerPage={per_page}
            page={current_page - 1 || 0}
            sx={{
                position: "sticky",
                bottom: 0,
                backgroundColor: "white",
            }}
            onPageChange={(e, page) => {
                handlePageChange("page", page + 1);
            }}
            onRowsPerPageChange={(e) => {
                handleRowsPerPageChange("per_page", e.target.value);
            }}
        />
    );
};

export default TablePaginationComponent;
