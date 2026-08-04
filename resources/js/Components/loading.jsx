import { Box, CircularProgress, styled } from '@mui/material'
import PropTypes from 'prop-types'

const Container = styled(Box)(({ theme }) => ({
  display: 'grid',
  placeContent: 'center',
  backdropFilter: 'blur(3.5px)',
  backgroundColor: 'rgba(255, 255, 255, 0.15)',
  color: theme.palette.primary.main,
}))

export const Loading = ({ loading }) => {
  return loading ? (
    <Container minWidth='100%' minHeight='100%' position='absolute' zIndex={999}>
      <CircularProgress />
    </Container>
  ) : null
}

Loading.defaultProps = { loading: false }

Loading.propTypes = { loading: PropTypes.bool.isRequired }
