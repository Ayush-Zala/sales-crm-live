import { Card, CardContent, Stack, Typography } from "@mui/material";

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
}) => {
    const formatedCount = format
        ? new Intl.NumberFormat("en-IN").format(count)
        : count;

    return (
        <Card sx={{ borderTop: 3, borderTopColor: "primary.main" }}>
            <CardContent sx={{ ":last-child": { paddingBottom: 2 } }}>
                <Stack
                    direction="row"
                    justifyContent="space-between"
                    alignItems="center"
                >
                    <Stack direction="column" spacing={0.5}>
                        <Typography
                            color="text.secondary"
                            fontWeight="600"
                            variant="body1"
                        >
                            {title}
                        </Typography>
                        <Typography
                            variant="h4"
                            sx={{
                                width: "fit-content",
                                cursor: isLinkOnCount ? "pointer" : "default",
                                ":hover": isLinkOnCount
                                    ? { color: "primary.main" }
                                    : {},
                            }}
                            onClick={handleClickOnCount}
                        >
                            {`${formatedCount} ${
                                extraCount ? "/ " + extraCount : ""
                            }`}
                        </Typography>
                    </Stack>

                    {/* Conditionally show filter dropdown if isFilterAllowed is true */}
                    {isFilterAllowed && (
                        <FilterComponent
                            filter={filter}
                            setFilter={setFilter}
                        />
                    )}
                </Stack>
            </CardContent>
        </Card>
    );
};

export default MetaDataCard;
