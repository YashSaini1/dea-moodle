import React from 'react';

import '@scss/react/widgets/tablesWidget/entities/controlPanel/ControlPanel.scss';
import { ScaleControl } from './controls/scaleControl/ScaleControl';

export const ControlPanel = () => {
  return (
    <div className='ControlPanel'>
      <ScaleControl />
    </div>
  );
};
