import { Table } from "@devexpress/dx-react-grid-material-ui";
import { Skeleton } from "@mui/material";
import { times } from "lodash";
import { ErrorElement } from "./common/error-element";

export const NoDataCell = ({
    loading,
    className,
    tableColumn,
    tableRow,
    colSpan,
    getMessage,
    style,
}) => {
    return loading ? (
        times(colSpan, (index) => (
            <Table.Cell
                key={index}
                tableRow={tableRow}
                style={style}
                className={className}
                tableColumn={tableColumn}
            >
                <Skeleton />
            </Table.Cell>
        ))
    ) : (
        <Table.Cell
            colSpan={colSpan}
            tableRow={tableRow}
            className={className}
            tableColumn={tableColumn}
            style={{
                ...style,
                border: 0,
                padding: 0,
                // borderBottom: '1px solid rgba(224, 224, 224, 1)',
            }}
        >
            <ErrorElement error={getMessage("noData")} />
        </Table.Cell>
    );
};
