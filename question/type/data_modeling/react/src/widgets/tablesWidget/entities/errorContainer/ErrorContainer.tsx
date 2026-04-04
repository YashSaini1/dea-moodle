import React from 'react';

import { getErrorMessage } from '@store/redux/stage/stageSelectors';
import { useSelector } from 'react-redux';

import '@scss/react/widgets/tablesWidget/entities/errorContainer/ErrorContainer.scss';

export const ErrorContainer = () => {
  const errorMessage = useSelector(getErrorMessage);

  if (errorMessage) {
    return <div className='ErrorContainer'>{errorMessage}</div>;
  }

  return null;
};
