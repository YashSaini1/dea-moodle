import { IStageState } from '@projectTypes/interfaces';
import { createSlice, PayloadAction } from '@reduxjs/toolkit';

const initialState: IStageState = {
  nodes: [],
  edges: [],
  connectionSourceTempNode: null,
  newEdge: null,
  editorText: '',
  errorMessage: '',
  sourceTableId: '',
  isConnectionComplete: false,
  isCursorLeave: false,
  isNeedToRemoveTempLine: false,
  isTableDragging: false,
};

const stageSlice = createSlice({
  name: 'stageSlice',
  initialState,
  reducers: {
    setIsCursorLeave: (state, action: PayloadAction<IStageState['isCursorLeave']>) => {
      state.isCursorLeave = action.payload;
    },
    setSourceTableId: (state, action: PayloadAction<IStageState['sourceTableId']>) => {
      state.sourceTableId = action.payload;
    },
    setEditorText: (state, action: PayloadAction<IStageState['editorText']>) => {
      state.editorText = action.payload;
    },
    setErrorMessage: (state, action: PayloadAction<IStageState['errorMessage']>) => {
      state.errorMessage = action.payload;
    },
    setConnectionSourceTempNode: (
      state,
      action: PayloadAction<IStageState['connectionSourceTempNode']>,
    ) => {
      state.connectionSourceTempNode = action.payload;
    },
    setNewEdge: (state, action: PayloadAction<IStageState['newEdge']>) => {
      state.newEdge = action.payload;
    },
    setIsConnectionComplete: (
      state,
      action: PayloadAction<IStageState['isConnectionComplete']>,
    ) => {
      state.isConnectionComplete = action.payload;
    },
    setIsNeedToRemoveTempLine: (
      state,
      action: PayloadAction<IStageState['isNeedToRemoveTempLine']>,
    ) => {
      state.isNeedToRemoveTempLine = action.payload;
    },
    setIsTableDragging: (
      state,
      action: PayloadAction<IStageState['isTableDragging']>,
    ) => {
      state.isTableDragging = action.payload;
    },
  },
});

export default stageSlice.reducer;
export const {
  setSourceTableId,
  setEditorText,
  setErrorMessage,
  setIsNeedToRemoveTempLine,
  setNewEdge,
  setIsConnectionComplete,
  setConnectionSourceTempNode,
  setIsCursorLeave,
  setIsTableDragging,
} = stageSlice.actions;
