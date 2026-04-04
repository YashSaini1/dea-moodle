import React, { useLayoutEffect, useState } from 'react';

import '@scss/react/widgets/tablesWidget/entities/controlPanel/scaleControl/ScaleControl.scss';
import 'rc-slider/assets/index.css';
import {
  DEFAULT_ZOOM,
  MAX_ZOOM,
  MIN_ZOOM,
  PERCENT_MULTIPLIER,
} from '@constants/constants';
import { OnSliderValueChangeParams } from '@projectTypes/types';
import Slider from 'rc-slider';
import { useViewport, useReactFlow } from 'reactflow';

export const ScaleControl = () => {
  const defaultSliderZoom = DEFAULT_ZOOM * PERCENT_MULTIPLIER;
  const maxSliderZoom = MAX_ZOOM * PERCENT_MULTIPLIER;
  const minSliderZoom = MIN_ZOOM * PERCENT_MULTIPLIER;

  const reactFlowInstance = useReactFlow();
  const { zoom: currentZoom } = useViewport();
  const [sliderZoom, setSliderZoom] = useState<number>(defaultSliderZoom);

  const onSliderValueChange = (integerScaleValue: OnSliderValueChangeParams) => {
    if (typeof integerScaleValue === 'number') {
      const fractionalScaleValue = integerScaleValue / PERCENT_MULTIPLIER;

      setSliderZoom(integerScaleValue);
      reactFlowInstance.zoomTo(fractionalScaleValue);
    }
  };

  useLayoutEffect(() => {
    setSliderZoom(Math.round(currentZoom * PERCENT_MULTIPLIER));
  }, [currentZoom]);

  return (
    <div className='ScaleControlContainer'>
      <div className='ScaleControl'>
        <Slider
          max={maxSliderZoom}
          min={minSliderZoom}
          onChange={onSliderValueChange}
          value={sliderZoom}
        />
      </div>
      <span className='ScalePercent'>{`${sliderZoom}%`}</span>
    </div>
  );
};
