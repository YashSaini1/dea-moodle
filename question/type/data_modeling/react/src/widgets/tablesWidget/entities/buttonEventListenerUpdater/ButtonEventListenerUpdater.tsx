import React, { useEffect } from 'react';

import { IButtonEventListenerUpdaterProps } from '@projectTypes/interfaces';

import { setChangesOnInput } from './functions/setChangesOnInput';

export const ButtonEventListenerUpdater = (props: IButtonEventListenerUpdaterProps) => {
  const { buttonIds, nodes, edges, editorText } = props;

  const button1 = document.getElementById(buttonIds[0]) as HTMLInputElement;
  const button2 = document.getElementById(buttonIds[1]) as HTMLInputElement;

  const onButtonClick = () => {
    setChangesOnInput({ nodes, edges, editorText });
  };

  useEffect(() => {
    if (button1) {
      button1.addEventListener('click', onButtonClick);
    }
    if (button2) {
      button2.addEventListener('click', onButtonClick);
    }

    return () => {
      if (button1) {
        button1.removeEventListener('click', onButtonClick);
      }
      if (button2) {
        button2.removeEventListener('click', onButtonClick);
      }
    };
  }, []);

  return <div style={{ visibility: 'hidden' }} />;
};
