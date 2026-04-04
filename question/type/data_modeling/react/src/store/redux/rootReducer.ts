import { combineReducers } from '@reduxjs/toolkit';
import stage from '@store/redux/stage/stageSlice';

export const rootReducer = combineReducers({
  stage,
});

export type RootState = ReturnType<typeof rootReducer>;
